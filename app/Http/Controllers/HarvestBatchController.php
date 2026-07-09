<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\CropCycle;
use App\Models\HarvestBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HarvestBatchController extends Controller
{
    public function index(Request $request)
    {
        $query = HarvestBatch::with('cropCycle.crop', 'block', 'harvestedBy')->latest('harvest_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('quality_grade', 'like', "%{$search}%")
                  ->orWhereHas('cropCycle', fn($c) => $c->where('season_name', 'like', "%{$search}%"))
                  ->orWhereHas('block', fn($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        $batches = $query->get();
        return view('harvest-batches.index', compact('batches', 'search'));
    }

    public function create()
    {
        $cropCycles = CropCycle::with('block', 'crop')->orderBy('season_name')->get();
        $workers = User::orderBy('name')->get();
        return view('harvest-batches.create', compact('cropCycles', 'workers'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateBatch($request);
        $this->assertNoActivePhi($validated['crop_cycle_id']);

        HarvestBatch::create($validated);

        return redirect()->route('harvest-batches.index')
            ->with('success', 'Harvest batch recorded.');
    }

    public function show(HarvestBatch $harvestBatch)
    {
        $harvestBatch->load('cropCycle.crop', 'cropCycle.block', 'block', 'harvestedBy', 'packhouseLots');
        return view('harvest-batches.show', compact('harvestBatch'));
    }

    public function edit(HarvestBatch $harvestBatch)
    {
        $cropCycles = CropCycle::with('block', 'crop')->orderBy('season_name')->get();
        $workers = User::orderBy('name')->get();
        return view('harvest-batches.edit', compact('harvestBatch', 'cropCycles', 'workers'));
    }

    public function update(Request $request, HarvestBatch $harvestBatch)
    {
        $validated = $this->validateBatch($request);

        $harvestBatch->update($validated);

        return redirect()->route('harvest-batches.show', $harvestBatch)
            ->with('success', 'Harvest batch updated.');
    }

    public function destroy(HarvestBatch $harvestBatch)
    {
        $harvestBatch->delete();

        return redirect()->route('harvest-batches.index')
            ->with('success', 'Harvest batch deleted.');
    }

    /**
     * Business rule: a harvest entry is blocked while an active pre-harvest
     * interval window applies to the block — enforced as hard validation.
     */
    private function assertNoActivePhi(int $cropCycleId): void
    {
        $cycle = CropCycle::find($cropCycleId);

        if ($cycle && $cycle->hasActivePreHarvestInterval()) {
            throw ValidationException::withMessages([
                'crop_cycle_id' => 'Harvest is blocked: an active pre-harvest interval applies to this crop cycle.',
            ]);
        }
    }

    private function validateBatch(Request $request): array
    {
        return $request->validate([
            'crop_cycle_id' => 'required|exists:crop_cycles,id',
            'block_id' => 'nullable|exists:blocks,id',
            'harvest_date' => 'required|date',
            'quantity_kg' => 'required|numeric|min:0',
            'quality_grade' => 'nullable|string|max:50',
            'rejects_kg' => 'nullable|numeric|min:0',
            'harvested_by' => 'nullable|exists:users,id',
        ]);
    }
}
