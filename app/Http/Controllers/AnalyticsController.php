<?php

namespace App\Http\Controllers;

use App\Models\KpiSnapshot;
use App\Services\AlertService;
use App\Services\KpiSnapshotService;

class AnalyticsController extends Controller
{
    public function index(AlertService $alertService)
    {
        $latestDate = KpiSnapshot::max('snapshot_date');

        $snapshots = $latestDate
            ? KpiSnapshot::where('snapshot_date', $latestDate)->get()->keyBy('key')
            : collect();

        // Proactive alerts aggregated from across the modules.
        $alerts = $alertService->collect();

        return view('analytics.index', compact('snapshots', 'latestDate', 'alerts'));
    }

    public function recompute(KpiSnapshotService $service)
    {
        $service->recompute();

        return redirect()->route('analytics.index')
            ->with('success', 'KPI snapshots recomputed.');
    }
}
