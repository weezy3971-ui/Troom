<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\SeasonalBudget;
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
        $cropCycle->load('block.farm', 'crop', 'seasonalBudget');
        return view('crop-cycles.show', compact('cropCycle'));
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
            'block_id' => 'required|exists:blocks,id',
            'crop_id' => 'required|exists:crops,id',
            'season_name' => 'required|string|max:255',
            'planting_date' => 'nullable|date',
            'expected_harvest_date' => 'nullable|date|after_or_equal:planting_date',
        ]);

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

        $cropCycle->update(['status' => 'active']);

        return back()->with('success', 'Crop cycle activated successfully.');
    }

    /**
     * Complete a crop cycle.
     */
    public function complete(CropCycle $cropCycle)
    {
        $cropCycle->update(['status' => 'completed']);

        return back()->with('success', 'Crop cycle marked as completed.');
    }

    /**
     * Cancel a crop cycle.
     */
    public function cancel(CropCycle $cropCycle)
    {
        $cropCycle->update(['status' => 'cancelled']);

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
        $cropCycle->delete();

        return redirect()->route('crop-cycles.index')
            ->with('success', 'Crop cycle deleted successfully.');
    }
}
