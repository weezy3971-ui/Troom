<?php

namespace App\Services;

use App\Models\CropCycle;
use App\Models\Dispatch;
use App\Models\Farm;
use App\Models\HarvestBatch;
use App\Models\KpiSnapshot;
use App\Models\SalesOrder;
use Carbon\Carbon;

/**
 * Aggregates data from every module into precomputed KPI snapshot rows.
 * The executive dashboard reads only from these snapshots — it never
 * aggregates live on page load (per Module 17 business rule).
 */
class KpiSnapshotService
{
    public function recompute(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();

        $harvestToday = (float) HarvestBatch::whereDate('harvest_date', $date)->sum('quantity_kg');

        $totalHarvest = (float) HarvestBatch::sum('quantity_kg');
        $totalRejects = (float) HarvestBatch::sum('rejects_kg');

        $activeAcres = (float) CropCycle::where('status', 'active')
            ->with('block')->get()->sum(fn($c) => optional($c->block)->size_acres ?? 0);
        $yieldPerAcre = $activeAcres > 0 ? $totalHarvest / $activeAcres : 0;

        $revenue = (float) SalesOrder::with('lines')->get()->sum(fn($o) => $o->orderValue());

        $totalCost = (float) \App\Models\CostAllocation::sum('amount');
        $costPerKg = $totalHarvest > 0 ? $totalCost / $totalHarvest : 0;

        $grossMargin = $revenue - $totalCost;

        $ordersPending = SalesOrder::whereNotIn('status', ['fulfilled', 'cancelled'])->count();

        $qualityRejection = $totalHarvest > 0 ? ($totalRejects / $totalHarvest) * 100 : 0;

        $totalDispatch = Dispatch::count();
        $deliveredDispatch = Dispatch::where('status', 'delivered')->count();
        $truckUtilisation = $totalDispatch > 0 ? ($deliveredDispatch / $totalDispatch) * 100 : 0;

        // Simple farm-health proxy: share of cycles that are active and on-budget.
        $cycles = CropCycle::with('seasonalBudget', 'costAllocations')->get();
        $healthy = $cycles->filter(fn($c) => $c->status === 'active' && ! $c->isBudgetExceeded())->count();
        $farmHealth = $cycles->count() > 0 ? ($healthy / $cycles->count()) * 100 : 0;

        $cashFlowForecast = $revenue - $totalCost;

        $kpis = [
            ['key' => 'harvest_today',      'value' => $harvestToday,      'unit' => 'kg'],
            ['key' => 'yield_per_acre',     'value' => $yieldPerAcre,      'unit' => 'kg/acre'],
            ['key' => 'revenue',            'value' => $revenue,           'unit' => 'KES'],
            ['key' => 'cost_per_kg',        'value' => $costPerKg,         'unit' => 'KES/kg'],
            ['key' => 'gross_margin',       'value' => $grossMargin,       'unit' => 'KES'],
            ['key' => 'orders_pending',     'value' => $ordersPending,     'unit' => 'orders'],
            ['key' => 'quality_rejection',  'value' => $qualityRejection,  'unit' => '%'],
            ['key' => 'truck_utilisation',  'value' => $truckUtilisation,  'unit' => '%'],
            ['key' => 'farm_health',        'value' => $farmHealth,        'unit' => '%'],
            ['key' => 'cash_flow_forecast', 'value' => $cashFlowForecast,  'unit' => 'KES'],
        ];

        foreach ($kpis as $kpi) {
            // Match on a Carbon (start of day) so the bound value is formatted
            // to the same 'Y-m-d H:i:s' the date-cast column stores. Passing a
            // bare 'Y-m-d' string never matches, causing duplicate-insert
            // UNIQUE violations on recompute.
            KpiSnapshot::updateOrCreate(
                ['snapshot_date' => $date->copy()->startOfDay(), 'key' => $kpi['key']],
                ['value' => $kpi['value'], 'unit' => $kpi['unit']]
            );
        }

        return $kpis;
    }
}
