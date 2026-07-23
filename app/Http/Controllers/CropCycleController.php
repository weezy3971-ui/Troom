<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\CropCycleSchedulePoint;
use App\Models\CropCycleTemplate;
use App\Models\SeasonalBudget;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class CropCycleController extends Controller
{
    public function index(Request $request)
    {
        $query = CropCycle::with('block.farm', 'crop', 'seasonalBudget');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('season_name', 'like', "%{$search}%")
                  ->orWhereHas('block', fn($b) => $b->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('block.farm', fn($f) => $f->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('crop', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $cropCycles = $query->get();
        return view('crop-cycles.index', compact('cropCycles', 'search'));
    }

    public function create()
    {
        $blocks = Block::with('farm')->orderBy('name')->get();
        $crops = Crop::orderBy('name')->get();
        $templates = CropCycleTemplate::where('is_active', true)->orderBy('crop_name')->get();

        return view('crop-cycles.create', compact('blocks', 'crops', 'templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'block_id' => 'required|exists:blocks,id',
            'crop_id' => 'required|exists:crops,id',
            'crop_cycle_template_id' => 'required|exists:crop_cycle_templates,id',
            'season_name' => 'required|string|max:255',
            'planting_date' => 'nullable|date',
            'expected_harvest_date' => 'nullable|date|after_or_equal:planting_date',
        ]);

        // The template knows how long the cycle runs, so the harvest date
        // follows from the planting date unless one was given explicitly.
        if (empty($validated['expected_harvest_date']) && ! empty($validated['planting_date'])) {
            $template = CropCycleTemplate::find($validated['crop_cycle_template_id']);
            $validated['expected_harvest_date'] = \Illuminate\Support\Carbon::parse($validated['planting_date'])
                ->addDays($template->total_cycle_days)
                ->toDateString();
        }

        // Business rule: a block can only have one active crop cycle at a time
        $activeExists = CropCycle::where('block_id', $validated['block_id'])
            ->where('status', 'active')
            ->exists();

        if ($activeExists) {
            return back()->withInput()
                ->with('error', 'This block already has an active crop cycle.');
        }

        $cropCycle = CropCycle::create($validated);

        return redirect()->route('crop-cycles.show', $cropCycle)
            ->with('success', 'Crop cycle created successfully.');
    }

    public function show(CropCycle $cropCycle)
    {
        $cropCycle->load(
            'block.farm', 'crop', 'seasonalBudget', 'costAllocations', 'plantings', 'harvestBatches',
            'germinationChecks.recordedBy', 'plantPopulationCounts.recordedBy', 'yieldForecasts.recordedBy',
            'template.stages', 'template.schedulePoints.stage',
            'activities.schedulePoint', 'activities.performedBy'
        );
        $projection = (new \App\Services\YieldProjectionService($cropCycle))->summary();

        // The template schedule resolved onto this cycle's calendar: what is
        // due, what is overdue, and what has been logged.
        $schedule = $cropCycle->resolvedSchedule();
        $compliance = $cropCycle->sprayComplianceRate();
        $activityTypes = CropCycleSchedulePoint::ACTIVITY_TYPES;

        return view('crop-cycles.show', compact(
            'cropCycle', 'projection', 'schedule', 'compliance', 'activityTypes'
        ));
    }

    public function edit(CropCycle $cropCycle)
    {
        $blocks = Block::with('farm')->orderBy('name')->get();
        $crops = Crop::orderBy('name')->get();
        $templates = CropCycleTemplate::where('is_active', true)->orderBy('crop_name')->get();

        return view('crop-cycles.edit', compact('cropCycle', 'blocks', 'crops', 'templates'));
    }

    public function update(Request $request, CropCycle $cropCycle)
    {
        $validated = $request->validate([
            'block_id'               => 'required|exists:blocks,id',
            'crop_id'                => 'required|exists:crops,id',
            'crop_cycle_template_id' => 'required|exists:crop_cycle_templates,id',
            'season_name'            => 'required|string|max:255',
            'planting_date'          => 'nullable|date',
            'expected_harvest_date'  => 'nullable|date|after_or_equal:planting_date',
        ]);

        // Business rule: a block can only have one active crop cycle at a time.
        // Re-check when block_id is being changed on an active cycle.
        if (
            $cropCycle->status === 'active'
            && (int) $validated['block_id'] !== (int) $cropCycle->block_id
        ) {
            $blockAlreadyActive = CropCycle::where('block_id', $validated['block_id'])
                ->where('status', 'active')
                ->where('id', '!=', $cropCycle->id)
                ->exists();

            if ($blockAlreadyActive) {
                return back()->withInput()
                    ->with('error', 'Cannot move this cycle: the target block already has an active crop cycle.');
            }
        }

        $cropCycle->update($validated);

        return redirect()->route('crop-cycles.show', $cropCycle)
            ->with('success', 'Crop cycle updated successfully.');
    }

    /**
     * Activate a crop cycle.
     * Business rules: cannot activate without budget; one active per block.
     */
    public function activate(CropCycle $cropCycle)
    {
        if (!$cropCycle->canActivate()) {
            return back()->with('error', 'Cannot activate: a seasonal budget must be set first.');
        }

        if ($cropCycle->blockHasActiveCycle()) {
            return back()->with('error', 'Cannot activate: this block already has an active crop cycle.');
        }

        ActivityLogger::as('activated', fn () => $cropCycle->update(['status' => 'active']));

        return back()->with('success', 'Crop cycle activated successfully.');
    }

    /**
     * Complete a crop cycle.
     */
    public function complete(CropCycle $cropCycle)
    {
        ActivityLogger::as('completed', fn () => $cropCycle->update(['status' => 'completed']));

        return back()->with('success', 'Crop cycle marked as completed.');
    }

    /**
     * Cancel a crop cycle.
     */
    public function cancel(CropCycle $cropCycle)
    {
        ActivityLogger::as('cancelled', fn () => $cropCycle->update(['status' => 'cancelled']));

        return back()->with('success', 'Crop cycle cancelled.');
    }

    /**
     * Set or update the seasonal budget for a crop cycle.
     */
    public function setBudget(Request $request, CropCycle $cropCycle)
    {
        $validated = $request->validate([
            'labour_budget' => 'required|numeric|min:0',
            'input_budget' => 'required|numeric|min:0',
            'irrigation_budget' => 'required|numeric|min:0',
            'overhead_budget' => 'required|numeric|min:0',
        ]);

        $validated['total_budget'] = $validated['labour_budget']
            + $validated['input_budget']
            + $validated['irrigation_budget']
            + $validated['overhead_budget'];

        $cropCycle->seasonalBudget()->updateOrCreate(
            ['crop_cycle_id' => $cropCycle->id],
            $validated
        );

        return back()->with('success', 'Seasonal budget saved successfully.');
    }

    public function destroy(CropCycle $cropCycle)
    {
        // Business rule: do not delete a cycle that already has harvest or cost data.
        $harvestCount = $cropCycle->harvestBatches()->count();
        $costCount    = $cropCycle->costAllocations()->count();

        if ($harvestCount > 0 || $costCount > 0) {
            $refs = [];
            if ($harvestCount > 0) $refs[] = "{$harvestCount} harvest batch(es)";
            if ($costCount    > 0) $refs[] = "{$costCount} cost allocation(s)";
            $detail = implode(' and ', $refs);
            return redirect()->route('crop-cycles.index')
                ->with('error', "Cannot delete \"{$cropCycle->season_name}\": it has {$detail} linked to it.");
        }

        $cropCycle->delete();

        return redirect()->route('crop-cycles.index')
            ->with('success', 'Crop cycle deleted successfully.');
    }
}
