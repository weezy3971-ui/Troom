<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\CropCycle;
use App\Models\InventoryItem;
use App\Models\SalesOrder;

/**
 * Aggregates the proactive alert conditions the spec defines across modules
 * into a single feed consumed by the notifications page and executive dashboard.
 */
class AlertService
{
    /**
     * @return array<int, array{type: string, severity: string, message: string, module: string}>
     */
    public function collect(): array
    {
        $alerts = [];

        // Module 16 — budget_exceeded
        foreach (CropCycle::with('seasonalBudget', 'costAllocations', 'block', 'crop')->get() as $cycle) {
            if ($cycle->isBudgetExceeded()) {
                $alerts[] = [
                    'type' => 'budget_exceeded',
                    'severity' => 'danger',
                    'module' => 'Finance',
                    'message' => "Budget exceeded for {$cycle->season_name} ({$cycle->block->name}) — actual KES "
                        . number_format($cycle->actualCost(), 0) . " vs budget KES "
                        . number_format($cycle->seasonalBudget->total_budget, 0) . '.',
                ];
            }
        }

        // Module 10 — low_inventory
        foreach (InventoryItem::with('transactions')->get() as $item) {
            if ($item->isLowStock()) {
                $alerts[] = [
                    'type' => 'low_inventory',
                    'severity' => 'warning',
                    'module' => 'Inventory',
                    'message' => "Low stock: {$item->name} is at " . number_format($item->currentStock(), 0)
                        . " {$item->unit}, below its reorder level of " . number_format($item->reorder_level, 0) . '.',
                ];
            }
        }

        // Module 14 — order_at_risk
        foreach (SalesOrder::with('customer', 'lines')->get() as $order) {
            if ($order->isAtRisk()) {
                $alerts[] = [
                    'type' => 'order_at_risk',
                    'severity' => 'warning',
                    'module' => 'Sales',
                    'message' => "Order at risk: {$order->customer->name} order #{$order->id} is under-allocated near its delivery date.",
                ];
            }
        }

        // Module 9 — pump_failure
        foreach (Asset::where('type', 'pump')->where('status', 'down')->get() as $pump) {
            $alerts[] = [
                'type' => 'pump_failure',
                'severity' => 'danger',
                'module' => 'Machinery',
                'message' => "Pump failure: {$pump->name} is down.",
            ];
        }

        return $alerts;
    }

    public function count(): int
    {
        return count($this->collect());
    }
}
