<?php

namespace App\Http\Controllers;

use App\Models\CropCycle;
use App\Models\CropCycleSchedulePoint;
use App\Models\PlantingCycleActivity;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Logging what was actually done against a cycle. Logging against a schedule
 * point closes the matching task; logging without one records ad hoc work.
 */
class PlantingCycleActivityController extends Controller
{
    public function store(Request $request, CropCycle $cropCycle)
    {
        $validated = $request->validate([
            'crop_cycle_schedule_point_id' => 'nullable|integer',
            'activity_type' => 'required|in:' . implode(',', array_keys(CropCycleSchedulePoint::ACTIVITY_TYPES)),
            'product_name' => 'nullable|string|max:255',
            'performed_date' => 'required|date|before_or_equal:today',
            'dosage' => 'nullable|string|max:255',
            'cost_kes' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $point = null;

        if (! empty($validated['crop_cycle_schedule_point_id'])) {
            // The point must belong to the template this cycle is running —
            // otherwise the completion would close a task on another plan.
            $point = CropCycleSchedulePoint::query()
                ->where('crop_cycle_template_id', $cropCycle->crop_cycle_template_id)
                ->find($validated['crop_cycle_schedule_point_id']);

            abort_unless($point !== null, 404);

            $alreadyLogged = $cropCycle->activities()
                ->where('crop_cycle_schedule_point_id', $point->id)
                ->exists();

            if ($alreadyLogged) {
                return back()->withInput()
                    ->with('error', 'That scheduled task has already been logged for this cycle.');
            }

            // Default the product to whatever the plan called for.
            $validated['product_name'] = $validated['product_name'] ?? null ?: $point->product_name;
            $validated['dosage'] = $validated['dosage'] ?? null ?: $point->dosage;
        }

        DB::transaction(function () use ($cropCycle, $validated, $point, $request) {
            $cropCycle->activities()->create($validated + [
                'performed_by' => $request->user()?->id,
            ]);

            // Close the task this completes, if the engine raised one.
            if ($point) {
                Task::where('crop_cycle_id', $cropCycle->id)
                    ->where('crop_cycle_schedule_point_id', $point->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'done', 'completed_at' => now()]);
            }
        });

        return back()->with('success', $point
            ? 'Activity logged and the scheduled task closed.'
            : 'Ad hoc activity logged.');
    }

    public function destroy(CropCycle $cropCycle, PlantingCycleActivity $activity)
    {
        abort_unless($activity->crop_cycle_id === $cropCycle->id, 404);

        DB::transaction(function () use ($activity) {
            // Deleting the completion reopens the task it closed, so the point
            // shows as outstanding again rather than silently disappearing.
            if ($activity->crop_cycle_schedule_point_id) {
                Task::where('crop_cycle_id', $activity->crop_cycle_id)
                    ->where('crop_cycle_schedule_point_id', $activity->crop_cycle_schedule_point_id)
                    ->update(['status' => 'pending', 'completed_at' => null]);
            }

            $activity->delete();
        });

        return back()->with('success', 'Activity removed.');
    }
}
