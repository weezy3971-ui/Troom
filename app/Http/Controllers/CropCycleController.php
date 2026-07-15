<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
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

    /**
     * Planting-schedule planner. A self-contained worksheet: pick a crop and a
     * planting date, and every phase (top-dressing, flowering, first harvest, …)
     * is recalculated client-side. Fill it in, tick tasks, then Save as PDF.
     *
     * Programs are curated per crop; switching crop re-renders the whole sheet
     * without a reload, so typed header details survive the switch.
     */
    public function planner(Request $request)
    {
        $programs = \App\Support\PlannerPrograms::all();

        $selected = $request->input('crop');
        if (! $selected || ! isset($programs[$selected])) {
            $selected = array_key_first($programs);
        }

        // Varieties are keyed by crop display name (curated ∪ what's in the DB).
        $varietiesByCrop = \App\Support\ReferenceData::varietiesByCrop();

        $blocks = Block::with('farm')->orderBy('name')->get()
            ->map(fn ($b) => $b->farm->name . ' — ' . $b->name)->all();

        return view('crop-cycles.planner', compact('programs', 'selected', 'varietiesByCrop', 'blocks'));
    }

    public function create()
    {
        $blocks = Block::with('farm')->orderBy('name')->get();
        $crops = Crop::orderBy('name')->get();
        return view('crop-cycles.create', compact('blocks', 'crops'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'block_id' => 'required|exists:blocks,id',
            'crop_id' => 'required|exists:crops,id',
            'season_name' => 'required|string|max:255',
            'planting_date' => 'nullable|date',
            'expected_harvest_date' => 'nullable|date|after_or_equal:planting_date',
        ]);

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
            'block.farm', 'crop', 'seasonalBudget', 'costAllocations', 'plantings', 'harvestBatches', 'stages',
            'germinationChecks.recordedBy', 'plantPopulationCounts.recordedBy', 'yieldForecasts.recordedBy'
        );
        $projection = (new \App\Services\YieldProjectionService($cropCycle))->summary();
        // A schedule can only be built once a planting date and an active crop
        // program both exist — used by the view to offer the "generate" action.
        $canSchedule = $cropCycle->planting_date && $cropCycle->crop?->activeProgram();
        return view('crop-cycles.show', compact('cropCycle', 'projection', 'canSchedule'));
    }

    public function edit(CropCycle $cropCycle)
    {
        $blocks = Block::with('farm')->orderBy('name')->get();
        $crops = Crop::orderBy('name')->get();
        return view('crop-cycles.edit', compact('cropCycle', 'blocks', 'crops'));
    }

    public function update(Request $request, CropCycle $cropCycle)
    {
        $validated = $request->validate([
            'block_id'              => 'required|exists:blocks,id',
            'crop_id'               => 'required|exists:crops,id',
            'season_name'           => 'required|string|max:255',
            'planting_date'         => 'nullable|date',
            'expected_harvest_date' => 'nullable|date|after_or_equal:planting_date',
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

        // Materialise the stage schedule from the crop's active program (if any).
        $created = $cropCycle->materialiseSchedule();
        $note = $created > 0 ? " {$created} program stages scheduled." : '';

        return back()->with('success', 'Crop cycle activated successfully.' . $note);
    }

    /**
     * (Re)build the stage schedule from the crop's active program. Useful when a
     * program is added or edited after the cycle was already activated.
     */
    public function generateSchedule(CropCycle $cropCycle)
    {
        $created = $cropCycle->materialiseSchedule();

        if ($created === 0) {
            return back()->with('error', 'Cannot generate schedule: set a planting date and an active crop program first.');
        }

        return back()->with('success', "Schedule generated — {$created} stages.");
    }

    /**
     * Tick off (or skip) a materialised stage.
     */
    public function updateStage(Request $request, CropCycle $cropCycle, \App\Models\CropCycleStage $stage)
    {
        abort_unless($stage->crop_cycle_id === $cropCycle->id, 404);

        $validated = $request->validate([
            'status' => 'required|in:pending,done,skipped',
        ]);

        $stage->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'done' ? now() : null,
        ]);

        return back()->with('success', 'Stage updated.');
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
