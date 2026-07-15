<?php

namespace App\Services\Ai;

use App\Models\KpiSnapshot;
use App\Services\AlertService;
use Carbon\Carbon;

/**
 * Gathers the farm's own data (KPI snapshots, trends, active alerts) into a
 * compact text block the AI can reason over. The model narrates these figures —
 * it never sources numbers itself, which keeps reports grounded and honest.
 */
class FarmContextAssembler
{
    public function __construct(private AlertService $alerts) {}

    /** Executive-scope context: latest KPIs, change vs the previous snapshot, and alerts. */
    public function executive(): string
    {
        $lines = [];
        $latestDate = KpiSnapshot::max('snapshot_date');

        $lines[] = '# Farm data snapshot';
        $lines[] = $latestDate
            ? 'As of: ' . Carbon::parse($latestDate)->format('M d, Y')
            : 'No KPI snapshots have been computed yet.';

        if ($latestDate) {
            $snaps = KpiSnapshot::where('snapshot_date', $latestDate)->get();

            $lines[] = '';
            $lines[] = '## Current KPIs';
            foreach ($snaps as $s) {
                $lines[] = "- {$s->key}: " . $this->num((float) $s->value) . " {$s->unit}";
            }

            $prevDate = KpiSnapshot::where('snapshot_date', '<', $latestDate)->max('snapshot_date');
            if ($prevDate) {
                $prev = KpiSnapshot::where('snapshot_date', $prevDate)->get()->keyBy('key');
                $lines[] = '';
                $lines[] = '## Change vs previous snapshot (' . Carbon::parse($prevDate)->format('M d, Y') . ')';
                foreach ($snaps as $s) {
                    if ($p = $prev->get($s->key)) {
                        $delta = (float) $s->value - (float) $p->value;
                        $sign = $delta >= 0 ? '+' : '';
                        $lines[] = "- {$s->key}: {$sign}" . $this->num($delta) . " {$s->unit}";
                    }
                }
            }
        }

        $alerts = $this->alerts->collect();
        $lines[] = '';
        $lines[] = '## Active alerts (' . count($alerts) . ')';
        if (empty($alerts)) {
            $lines[] = '- None';
        } else {
            foreach ($alerts as $a) {
                $lines[] = "- [{$a['severity']}] {$a['module']}: {$a['message']}";
            }
        }

        return implode("\n", $lines);
    }

    /** Trim trailing zeros so figures read cleanly in the prompt. */
    private function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }
}
