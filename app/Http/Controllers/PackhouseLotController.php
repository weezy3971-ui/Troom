<?php

namespace App\Http\Controllers;

use App\Models\HarvestBatch;
use App\Models\PackhouseLot;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PackhouseLotController extends Controller
{
    public function index(Request $request)
    {
        $query = PackhouseLot::with('harvestBatch.cropCycle.crop', 'latestQualityCheck')->latest('pack_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                  ->orWhere('traceability_code', 'like', "%{$search}%")
                  ->orWhere('packaging_type', 'like', "%{$search}%");
            });
        }

        $lots = $query->get();
        return view('packhouse-lots.index', compact('lots', 'search'));
    }

    public function create()
    {
        $harvestBatches = HarvestBatch::with('cropCycle.crop', 'block', 'packhouseLots')
            ->latest('harvest_date')->get();
        return view('packhouse-lots.create', compact('harvestBatches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'harvest_batch_id' => 'required|exists:harvest_batches,id',
            'lot_number' => 'nullable|string|max:255',
            'pack_date' => 'required|date',
            'quantity_packed' => 'required|numeric|min:0.01',
            'packaging_type' => 'nullable|string|max:255',
        ]);

        $this->assertWithinAvailable($validated['harvest_batch_id'], $validated['quantity_packed']);

        $validated['lot_number'] = $validated['lot_number'] ?: 'LOT-' . strtoupper(Str::random(6));
        // Business rule: the traceability code is generated at creation and is immutable.
        $validated['traceability_code'] = 'TRC-' . strtoupper(Str::random(10));

        PackhouseLot::create($validated);

        return redirect()->route('packhouse-lots.index')
            ->with('success', 'Packhouse lot created and traceability code generated.');
    }

    public function show(PackhouseLot $packhouseLot)
    {
        $packhouseLot->load('harvestBatch.cropCycle.crop', 'qualityChecks.inspector', 'salesOrderLines.salesOrder');
        return view('packhouse-lots.show', compact('packhouseLot'));
    }

    public function edit(PackhouseLot $packhouseLot)
    {
        $harvestBatches = HarvestBatch::with('cropCycle.crop', 'block')->latest('harvest_date')->get();
        return view('packhouse-lots.edit', compact('packhouseLot', 'harvestBatches'));
    }

    public function update(Request $request, PackhouseLot $packhouseLot)
    {
        // The traceability code and harvest batch are immutable; only packaging
        // metadata and quantity are editable.
        $validated = $request->validate([
            'lot_number' => 'required|string|max:255',
            'pack_date' => 'required|date',
            'quantity_packed' => 'required|numeric|min:0.01',
            'packaging_type' => 'nullable|string|max:255',
        ]);

        $this->assertWithinAvailable($packhouseLot->harvest_batch_id, $validated['quantity_packed'], $packhouseLot->id);

        $packhouseLot->update($validated);

        return redirect()->route('packhouse-lots.show', $packhouseLot)
            ->with('success', 'Packhouse lot updated.');
    }

    public function destroy(PackhouseLot $packhouseLot)
    {
        $packhouseLot->delete();

        return redirect()->route('packhouse-lots.index')
            ->with('success', 'Packhouse lot deleted.');
    }

    /**
     * Business rule: quantity packed cannot exceed the harvest batch's
     * quantity minus its recorded rejects (and anything already packed).
     */
    private function assertWithinAvailable(int $harvestBatchId, float $quantity, ?int $ignoreLotId = null): void
    {
        $batch = HarvestBatch::with('packhouseLots')->find($harvestBatchId);
        if (! $batch) {
            return;
        }

        $alreadyPacked = $batch->packhouseLots
            ->when($ignoreLotId, fn($lots) => $lots->where('id', '!=', $ignoreLotId))
            ->sum('quantity_packed');

        $available = $batch->netQuantity() - $alreadyPacked;

        if ($quantity > $available + 0.001) {
            throw ValidationException::withMessages([
                'quantity_packed' => "Quantity packed exceeds available weight ({$available} kg after rejects and existing lots).",
            ]);
        }
    }
}
