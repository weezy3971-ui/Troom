<?php

namespace App\Observers;

use App\Models\CostAllocation;
use App\Models\PlantingCycleActivity;

/**
 * An activity's cost is the cycle's cost. Logging work with a cost_kes mirrors
 * it into cost_allocations so it lands in the cycle's actual spend and the
 * budget-exceeded alert, without the field staff having to file an expense
 * separately.
 */
class PlantingCycleActivityObserver
{
    public function saved(PlantingCycleActivity $activity): void
    {
        // No cost logged, and none previously mirrored — nothing to do.
        if (! $activity->cost_kes || (float) $activity->cost_kes <= 0) {
            $this->clearMirror($activity);

            return;
        }

        CostAllocation::updateOrCreate(
            [
                'source_type' => 'planting_cycle_activity',
                'source_id' => $activity->id,
            ],
            [
                'crop_cycle_id' => $activity->crop_cycle_id,
                'block_id' => $activity->cropCycle?->block_id,
                'amount' => $activity->cost_kes,
                'allocation_date' => $activity->performed_date,
                'description' => trim($activity->activityLabel() . ' — ' . ($activity->product_name ?: 'unspecified input')),
            ]
        );
    }

    public function deleted(PlantingCycleActivity $activity): void
    {
        $this->clearMirror($activity);
    }

    private function clearMirror(PlantingCycleActivity $activity): void
    {
        CostAllocation::where('source_type', 'planting_cycle_activity')
            ->where('source_id', $activity->id)
            ->delete();
    }
}
