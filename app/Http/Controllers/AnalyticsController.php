<?php

namespace App\Http\Controllers;

use App\Models\CropCycle;
use App\Models\InventoryItem;
use App\Models\KpiSnapshot;
use App\Models\SalesOrder;
use App\Services\KpiSnapshotService;

class AnalyticsController extends Controller
{
    public function index()
    {
        $latestDate = KpiSnapshot::max('snapshot_date');

        $snapshots = $latestDate
            ? KpiSnapshot::where('snapshot_date', $latestDate)->get()->keyBy('key')
            : collect();

        // Proactive alerts aggregated from across the modules.
        $alerts = $this->collectAlerts();

        return view('analytics.index', compact('snapshots', 'latestDate', 'alerts'));
    }

    public function recompute(KpiSnapshotService $service)
    {
        $service->recompute();

        return redirect()->route('analytics.index')
            ->with('success', 'KPI snapshots recomputed.');
    }

    /**
     * Surface the alert conditions the spec defines across modules.
     */
    private function collectAlerts(): array
    {
        $alerts = [];

        foreach (CropCycle::with('seasonalBudget', 'costAllocations', 'block', 'crop')->get() as $cycle) {
            if ($cycle->isBudgetExceeded()) {
                $alerts[] = [
                    'type' => 'budget_exceeded',
                    'severity' => 'danger',
                    'message' => "Budget exceeded for {$cycle->season_name} ({$cycle->block->name}).",
                ];
            }
        }

        foreach (InventoryItem::with('transactions')->get() as $item) {
            if ($item->isLowStock()) {
                $alerts[] = [
                    'type' => 'low_inventory',
                    'severity' => 'warning',
                    'message' => "Low stock: {$item->name} is below its reorder level.",
                ];
            }
        }

        foreach (SalesOrder::with('customer', 'lines')->get() as $order) {
            if ($order->isAtRisk()) {
                $alerts[] = [
                    'type' => 'order_at_risk',
                    'severity' => 'warning',
                    'message' => "Order at risk: {$order->customer->name} order #{$order->id} is under-allocated near its delivery date.",
                ];
            }
        }

        return $alerts;
    }
}
