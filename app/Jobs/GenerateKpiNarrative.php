<?php

namespace App\Jobs;

use App\Models\KpiNarrative;
use App\Services\Ai\AiClient;
use App\Services\Ai\FarmContextAssembler;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Writes the executive dashboard's AI commentary for a given day. Runs on a
 * schedule and after a KPI recompute — never on page load, which is what keeps
 * the running cost near-zero (see docs/ai-companion-implementation-plan.md §5).
 */
class GenerateKpiNarrative implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?string $date = null) {}

    public function handle(AiClient $ai, FarmContextAssembler $assembler): void
    {
        $date = $this->date ? Carbon::parse($this->date) : Carbon::today();

        $narrative = KpiNarrative::firstOrNew(['narrative_date' => $date->toDateString()]);

        try {
            $context = $assembler->executive();

            $system = 'You are an agribusiness analyst for a Kenyan horticulture farm. '
                . 'Write a SHORT executive commentary (3-4 sentences, plain prose, no headings, no bullet points) '
                . 'on the farm KPIs provided. Say what changed and why it matters, then name the single most '
                . 'important thing to watch. Base every claim ONLY on the data given — never invent figures. '
                . 'Use KES for money. Be direct and specific; no preamble.';

            $user = "Here is today's farm data. Write the executive commentary.\n\n{$context}";

            $result = $ai->generate($system, $user, [
                'feature' => 'dashboard',
                'max_tokens' => 600,
            ]);

            $narrative->fill([
                'content' => $result['text'],
                'model' => $result['model'],
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
                'status' => 'completed',
                'error' => null,
            ])->save();
        } catch (Throwable $e) {
            $narrative->fill([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ])->save();
        }
    }
}
