<?php

namespace App\Jobs;

use App\Models\AiReport;
use App\Services\Ai\AiClient;
use App\Services\Ai\FarmContextAssembler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Assembles farm context, asks the model to write the report, and stores the
 * result. Dispatched synchronously today (dispatchSync) so reports are ready on
 * redirect; switch the controller to dispatch() once a queue worker runs.
 */
class GenerateAiReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $reportId) {}

    public function handle(AiClient $ai, FarmContextAssembler $assembler): void
    {
        $report = AiReport::find($this->reportId);
        if (! $report) {
            return;
        }

        try {
            $context = $assembler->executive();

            $system = 'You are an agribusiness analyst for a Kenyan horticulture farm ERP. '
                . 'Write a clear, concise executive report in Markdown, based ONLY on the data provided below. '
                . 'Never invent figures — every number you cite must come from the data. Use KES for money. '
                . 'Structure the report as: a one-paragraph headline summary, then "## Highlights", '
                . '"## Concerns" and "## Recommended actions" sections. Be specific and reference the actual figures.';

            $period = ($report->period_start?->format('M d, Y') ?? 'to date')
                . ' → ' . ($report->period_end?->format('M d, Y') ?? 'now');

            $user = "Generate a {$report->typeLabel()} for the period: {$period}.\n\n"
                . "Here is the current farm data:\n\n{$context}";

            $result = $ai->generate($system, $user, [
                'feature' => 'report',
                'user_id' => $report->generated_by,
                'max_tokens' => 2000,
            ]);

            $report->update([
                'content' => $result['text'],
                'model' => $result['model'],
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
                'status' => 'completed',
                'error' => null,
            ]);
        } catch (Throwable $e) {
            $report->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
