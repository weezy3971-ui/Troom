<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\NurseryBatch;
use Illuminate\Http\Request;

class NurseryBatchController extends Controller
{
    public function index(Request $request)
    {
        $query = NurseryBatch::with('crop')->withCount('plantings')->latest();

        if ($search = $request->input('search')) {
            $query->whereHas('crop', fn($c) => $c->where('name', 'like', "%{$search}%")->orWhere('variety', 'like', "%{$search}%"));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $batches = $query->get();
        return view('nursery-batches.index', compact('batches', 'search'));
    }

    public function create()
    {
        $crops = Crop::orderBy('name')->get();
        return view('nursery-batches.create', compact('crops'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'sow_date' => 'required|date',
            'expected_ready_date' => 'nullable|date|after_or_equal:sow_date',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|in:sown,growing,ready,transplanted',
        ]);

        NurseryBatch::create($validated);

        return redirect()->route('nursery-batches.index')
            ->with('success', 'Nursery batch created successfully.');
    }

    public function show(NurseryBatch $nurseryBatch)
    {
        $nurseryBatch->load('crop', 'plantings.cropCycle.block');
        $cropCycles = CropCycle::with('block', 'crop')->orderBy('season_name')->get();
        return view('nursery-batches.show', compact('nurseryBatch', 'cropCycles'));
    }

    public function edit(NurseryBatch $nurseryBatch)
    {
        $crops = Crop::orderBy('name')->get();
        return view('nursery-batches.edit', compact('nurseryBatch', 'crops'));
    }

    public function update(Request $request, NurseryBatch $nurseryBatch)
    {
        $validated = $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'sow_date' => 'required|date',
            'expected_ready_date' => 'nullable|date|after_or_equal:sow_date',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|in:sown,growing,ready,transplanted',
        ]);

        $nurseryBatch->update($validated);

        return redirect()->route('nursery-batches.show', $nurseryBatch)
            ->with('success', 'Nursery batch updated successfully.');
    }

    /**
     * Record a transplant: create a Planting linking this batch to a crop cycle.
     * Business rule: transplanted quantity cannot exceed the batch's remaining quantity;
     * the batch's status auto-updates to transplanted once a Planting is recorded.
     */
    public function transplant(Request $request, NurseryBatch $nurseryBatch)
    {
        $validated = $request->validate([
            'crop_cycle_id' => 'required|exists:crop_cycles,id',
            'quantity' => 'required|integer|min:1',
            'planting_date' => 'required|date',
        ]);

        if ($validated['quantity'] > $nurseryBatch->remainingQuantity()) {
            return back()->withInput()->with('error',
                'Transplant quantity exceeds the batch\'s remaining quantity ('.$nurseryBatch->remainingQuantity().').');
        }

        $nurseryBatch->plantings()->create($validated);
        $nurseryBatch->syncTransplantStatus();

        return back()->with('success', 'Transplant recorded and planting created.');
    }

    public function destroy(NurseryBatch $nurseryBatch)
    {
        $nurseryBatch->delete();

        return redirect()->route('nursery-batches.index')
            ->with('success', 'Nursery batch deleted successfully.');
    }
}
