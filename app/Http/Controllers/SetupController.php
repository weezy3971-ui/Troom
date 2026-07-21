<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Guided single-page setup wizard: Farm → Block → Crop → Crop Cycle in one
 * flow. Each step can either reuse an existing record or create a new one, so
 * a returning user isn't forced to re-create a farm/crop they already have.
 * Everything is submitted once and persisted in a single transaction, then the
 * user lands on the freshly-created (and editable) crop cycle.
 */
class SetupController extends Controller
{
    public function index()
    {
        $farms = Farm::orderBy('name')->get(['id', 'name']);
        $blocks = Block::orderBy('name')->get(['id', 'name', 'farm_id']);
        $crops = Crop::orderBy('name')->get([
            'id', 'name', 'variety', 'days_to_maturity',
            'default_labour_budget', 'default_input_budget',
            'default_irrigation_budget', 'default_overhead_budget',
        ]);

        return view('setup.index', compact('farms', 'blocks', 'crops'));
    }

    public function store(Request $request)
    {
        // A new farm has no existing blocks — force the block step to "new" so
        // the two selections can't contradict each other.
        if ($request->input('farm_mode') === 'new') {
            $request->merge(['block_mode' => 'new']);
        }

        $validated = $request->validate([
            // Step 1 — Farm
            'farm_mode' => 'required|in:existing,new',
            'existing_farm_id' => 'required_if:farm_mode,existing|nullable|exists:farms,id',
            'farm_name' => 'required_if:farm_mode,new|nullable|string|max:255',
            'farm_location' => 'required_if:farm_mode,new|nullable|string|max:255',
            'farm_size_acres' => 'required_if:farm_mode,new|nullable|numeric|min:0.01',
            'farm_latitude' => 'nullable|numeric|between:-90,90',
            'farm_longitude' => 'nullable|numeric|between:-180,180',

            // Step 2 — Block
            'block_mode' => 'required|in:existing,new',
            'existing_block_id' => 'required_if:block_mode,existing|nullable|exists:blocks,id',
            'block_name' => 'required_if:block_mode,new|nullable|string|max:255',
            'block_size_acres' => 'required_if:block_mode,new|nullable|numeric|min:0.01',
            'block_soil_type' => 'nullable|string|max:255',

            // Step 3 — Crop
            'crop_mode' => 'required|in:existing,new',
            'existing_crop_id' => 'required_if:crop_mode,existing|nullable|exists:crops,id',
            'crop_name' => 'required_if:crop_mode,new|nullable|string|max:255',
            'crop_type' => 'required_if:crop_mode,new|nullable|string|max:255',
            'crop_variety' => 'nullable|string|max:255',
            'crop_days_to_maturity' => 'nullable|integer|min:1',
            'crop_expected_yield_per_acre' => 'nullable|numeric|min:0',
            'crop_default_labour_budget' => 'nullable|numeric|min:0',
            'crop_default_input_budget' => 'nullable|numeric|min:0',
            'crop_default_irrigation_budget' => 'nullable|numeric|min:0',
            'crop_default_overhead_budget' => 'nullable|numeric|min:0',

            // Step 4 — Crop Cycle
            'season_name' => 'required|string|max:255',
            'planting_date' => 'nullable|date',
            'expected_harvest_date' => 'nullable|date|after_or_equal:planting_date',

            // Step 5 — Seasonal Budget (required so the cycle can be activated)
            'labour_budget' => 'required|numeric|min:0',
            'input_budget' => 'required|numeric|min:0',
            'irrigation_budget' => 'required|numeric|min:0',
            'overhead_budget' => 'required|numeric|min:0',
        ]);

        $budgetTotal = $validated['labour_budget'] + $validated['input_budget']
            + $validated['irrigation_budget'] + $validated['overhead_budget'];

        // A cycle can only be activated with a budget above zero.
        if ($budgetTotal <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'labour_budget' => 'Enter a seasonal budget greater than zero to activate the cycle.',
            ]);
        }

        $cropCycle = DB::transaction(function () use ($validated) {
            // Farm
            $farm = $validated['farm_mode'] === 'existing'
                ? Farm::findOrFail($validated['existing_farm_id'])
                : Farm::create([
                    'name' => $validated['farm_name'],
                    'location' => $validated['farm_location'],
                    'size_acres' => $validated['farm_size_acres'],
                    'latitude' => $validated['farm_latitude'] ?? null,
                    'longitude' => $validated['farm_longitude'] ?? null,
                ]);

            // Block
            $block = $validated['block_mode'] === 'existing'
                ? Block::findOrFail($validated['existing_block_id'])
                : Block::create([
                    'farm_id' => $farm->id,
                    'name' => $validated['block_name'],
                    'size_acres' => $validated['block_size_acres'],
                    'soil_type' => $validated['block_soil_type'] ?? null,
                ]);

            // Crop
            $crop = $validated['crop_mode'] === 'existing'
                ? Crop::findOrFail($validated['existing_crop_id'])
                : Crop::create([
                    'name' => $validated['crop_name'],
                    'variety' => $validated['crop_variety'] ?? null,
                    'crop_type' => $validated['crop_type'],
                    'days_to_maturity' => $validated['crop_days_to_maturity'] ?? null,
                    'expected_yield_per_acre' => $validated['crop_expected_yield_per_acre'] ?? null,
                    'default_labour_budget' => $validated['crop_default_labour_budget'] ?? null,
                    'default_input_budget' => $validated['crop_default_input_budget'] ?? null,
                    'default_irrigation_budget' => $validated['crop_default_irrigation_budget'] ?? null,
                    'default_overhead_budget' => $validated['crop_default_overhead_budget'] ?? null,
                ]);

            // Expected harvest falls back to planting date + crop maturity, matching
            // the standalone crop-cycle form's client-side auto-calc.
            $harvest = $validated['expected_harvest_date'] ?? null;
            if (! $harvest && ! empty($validated['planting_date']) && $crop->days_to_maturity) {
                $harvest = \Illuminate\Support\Carbon::parse($validated['planting_date'])
                    ->addDays((int) $crop->days_to_maturity)
                    ->toDateString();
            }

            $cycle = CropCycle::create([
                'block_id' => $block->id,
                'crop_id' => $crop->id,
                'season_name' => $validated['season_name'],
                'planting_date' => $validated['planting_date'] ?? null,
                'expected_harvest_date' => $harvest,
                'status' => 'planned',
            ]);

            // Seasonal budget entered in the wizard's final step.
            $cycle->seasonalBudget()->create([
                'labour_budget' => $validated['labour_budget'],
                'input_budget' => $validated['input_budget'],
                'irrigation_budget' => $validated['irrigation_budget'],
                'overhead_budget' => $validated['overhead_budget'],
                'total_budget' => $validated['labour_budget'] + $validated['input_budget']
                    + $validated['irrigation_budget'] + $validated['overhead_budget'],
            ]);

            // Activate now — unless the block already has an active cycle (one
            // active per block), in which case it stays planned.
            $blockBusy = CropCycle::where('block_id', $block->id)
                ->where('status', 'active')
                ->where('id', '!=', $cycle->id)
                ->exists();

            if (! $blockBusy) {
                $cycle->update(['status' => 'active']);
            }

            return $cycle;
        });

        if ($cropCycle->status === 'active') {
            return redirect()->route('crop-cycles.show', $cropCycle)
                ->with('success', 'Setup complete — your crop cycle is now active.');
        }

        return redirect()->route('crop-cycles.show', $cropCycle)
            ->with('error', 'Cycle created, but the block already has an active cycle — this one was saved as planned. Activate it once the other completes.');
    }
}
