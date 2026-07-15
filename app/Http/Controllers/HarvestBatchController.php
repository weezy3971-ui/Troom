<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\CropCycle;
use App\Models\HarvestBatch;
use App\Models\HarvestByProduct;
use App\Models\User;
use App\Support\ActivityLogger;
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
        $cropCycles = CropCycle::with('block', 'crop', 'sprayLogs')->orderBy('season_name')->get();
        $workers    = User::orderBy('name')->get();
        // IDs of cycles currently blocked by an active PHI window — used by the view to warn the user.
        $phiBlockedIds = $cropCycles->filter(fn($c) => $c->hasActivePreHarvestInterval())->pluck('id')->toArray();
        return view('harvest-batches.create', compact('cropCycles', 'workers', 'phiBlockedIds'));
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
        $harvestBatch->load(
            'cropCycle.crop',
            'cropCycle.block',
            'cropCycle.seasonalBudget',
            'cropCycle.harvestBatches',
            'block',
            'harvestedBy',
            'confirmedBy',
            'byProducts',
            'packhouseLots'
        );
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
        // Business rule: PHI block applies to edits as well as new entries.
        $this->assertNoActivePhi($validated['crop_cycle_id']);

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
     * Verify the weighed quantity. Records who confirmed and when.
     */
    public function confirm(HarvestBatch $harvestBatch)
    {
        ActivityLogger::as('confirmed', fn () => $harvestBatch->update([
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]));

        return back()->with('success', 'Harvest weight confirmed.');
    }

    /**
     * Record a by-product recovered from this batch (e.g. offcut leaves).
     */
    public function storeByProduct(Request $request, HarvestBatch $harvestBatch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity_kg' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $harvestBatch->byProducts()->create($validated);

        return back()->with('success', 'By-product recorded.');
    }

    public function destroyByProduct(HarvestBatch $harvestBatch, HarvestByProduct $byProduct)
    {
        // Ensure the by-product belongs to this batch before deleting.
        abort_unless($byProduct->harvest_batch_id === $harvestBatch->id, 404);

        $byProduct->delete();

        return back()->with('success', 'By-product removed.');
    }

    /**
     * Business rule: a harvest entry is blocked while an active pre-harvest
     * interval window applies to the block — enforced as hard validation.
     */
    private function assertNoActivePhi(int $cropCycleId): void
    {
        $cycle = CropCycle::with('sprayLogs')->find($cropCycleId);

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
