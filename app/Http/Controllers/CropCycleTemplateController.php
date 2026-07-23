<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\CropCycleSchedulePoint;
use App\Models\CropCycleStage;
use App\Models\CropCycleTemplate;
use Illuminate\Http\Request;

/**
 * Crop cycle templates — the reusable planting-to-harvest plan. A template
 * carries its growth stages and its spray/input schedule; the schedule points
 * are what the reminder engine reads once a block starts running the template.
 */
class CropCycleTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = CropCycleTemplate::with('crop')
            ->withCount(['stages', 'schedulePoints', 'cropCycles'])
            ->orderBy('crop_name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('crop_name', 'like', "%{$search}%")
                  ->orWhere('variety', 'like', "%{$search}%");
            });
        }

        $templates = $query->get();

        return view('crop-cycle-templates.index', compact('templates', 'search'));
    }

    public function create()
    {
        $crops = Crop::orderBy('name')->get();

        return view('crop-cycle-templates.create', compact('crops'));
    }

    public function store(Request $request)
    {
        $template = CropCycleTemplate::create($this->validateTemplate($request));

        return redirect()->route('crop-cycle-templates.show', $template)
            ->with('success', 'Template created. Add its stages, then the spray/input schedule.');
    }

    public function show(CropCycleTemplate $cropCycleTemplate)
    {
        $cropCycleTemplate->load('crop', 'stages', 'schedulePoints.stage');

        $activityTypes = CropCycleSchedulePoint::ACTIVITY_TYPES;

        return view('crop-cycle-templates.show', compact('cropCycleTemplate', 'activityTypes'));
    }

    public function edit(CropCycleTemplate $cropCycleTemplate)
    {
        $crops = Crop::orderBy('name')->get();

        return view('crop-cycle-templates.edit', compact('cropCycleTemplate', 'crops'));
    }

    public function update(Request $request, CropCycleTemplate $cropCycleTemplate)
    {
        $cropCycleTemplate->update($this->validateTemplate($request));

        return redirect()->route('crop-cycle-templates.show', $cropCycleTemplate)
            ->with('success', 'Template updated.');
    }

    public function destroy(CropCycleTemplate $cropCycleTemplate)
    {
        // A template in use is the plan of record for cycles already running
        // against it — deleting it would strip their schedule.
        $inUse = $cropCycleTemplate->cropCycles()->count();

        if ($inUse > 0) {
            return redirect()->route('crop-cycle-templates.index')
                ->with('error', "Cannot delete \"{$cropCycleTemplate->crop_name}\": {$inUse} crop cycle(s) are running it. Mark it inactive instead.");
        }

        $cropCycleTemplate->delete();

        return redirect()->route('crop-cycle-templates.index')
            ->with('success', 'Template deleted.');
    }

    public function storeStage(Request $request, CropCycleTemplate $cropCycleTemplate)
    {
        $validated = $request->validate([
            'stage_name' => 'required|string|max:255',
            'start_day_offset' => 'required|integer|min:0',
            'end_day_offset' => 'required|integer|min:0|gte:start_day_offset',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['sort_order'] = $validated['sort_order']
            ?? (($cropCycleTemplate->stages()->max('sort_order') ?? 0) + 1);

        $cropCycleTemplate->stages()->create($validated);

        return back()->with('success', 'Stage added.');
    }

    public function destroyStage(CropCycleTemplate $cropCycleTemplate, CropCycleStage $stage)
    {
        abort_unless($stage->crop_cycle_template_id === $cropCycleTemplate->id, 404);

        $stage->delete();

        return back()->with('success', 'Stage removed. Schedule points that sat in it are now unassigned.');
    }

    /**
     * Add a schedule point — the row the reminder engine reads.
     */
    public function storeSchedulePoint(Request $request, CropCycleTemplate $cropCycleTemplate)
    {
        $validated = $request->validate([
            'day_offset' => 'required|integer|min:0|max:' . $cropCycleTemplate->total_cycle_days,
            'activity_type' => 'required|in:' . implode(',', array_keys(CropCycleSchedulePoint::ACTIVITY_TYPES)),
            'product_name' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|max:255',
            'dosage' => 'nullable|string|max:255',
            'pre_harvest_interval_days' => 'nullable|integer|min:0',
            'crop_cycle_stage_id' => 'nullable|integer',
        ]);

        // A point may name a stage, but only one belonging to this template.
        if (! empty($validated['crop_cycle_stage_id'])) {
            abort_unless(
                $cropCycleTemplate->stages()->whereKey($validated['crop_cycle_stage_id'])->exists(),
                404
            );
        }

        $cropCycleTemplate->schedulePoints()->create($validated);

        return back()->with('success', 'Schedule point added — reminders will fire on this day for every cycle running this template.');
    }

    public function destroySchedulePoint(CropCycleTemplate $cropCycleTemplate, CropCycleSchedulePoint $point)
    {
        abort_unless($point->crop_cycle_template_id === $cropCycleTemplate->id, 404);

        $point->delete();

        return back()->with('success', 'Schedule point removed.');
    }

    private function validateTemplate(Request $request): array
    {
        $validated = $request->validate([
            'crop_id' => 'nullable|exists:crops,id',
            'crop_name' => 'required|string|max:255',
            'variety' => 'nullable|string|max:255',
            'total_cycle_days' => 'required|integer|min:1|max:1000',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
