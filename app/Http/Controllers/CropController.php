<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use Illuminate\Http\Request;

class CropController extends Controller
{
    public function index(Request $request)
    {
        $query = Crop::withCount('cropCycles');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('variety', 'like', "%{$search}%")
                  ->orWhere('crop_type', 'like', "%{$search}%");
            });
        }

        $crops = $query->get();
        return view('crops.index', compact('crops', 'search'));
    }

    public function create()
    {
        return view('crops.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'variety' => 'nullable|string|max:255',
            'crop_type' => 'required|string|max:255',
            'days_to_maturity' => 'nullable|integer|min:1',
            'expected_yield_per_acre' => 'nullable|numeric|min:0',
            'seeds_per_bed' => 'nullable|integer|min:0',
            'expected_yield_per_bed_kg' => 'nullable|numeric|min:0',
            'reference_price_per_kg' => 'nullable|numeric|min:0',
            'expected_germination_rate' => 'nullable|numeric|min:0|max:1',
            'default_labour_budget' => 'nullable|numeric|min:0',
            'default_input_budget' => 'nullable|numeric|min:0',
            'default_irrigation_budget' => 'nullable|numeric|min:0',
            'default_overhead_budget' => 'nullable|numeric|min:0',
        ]);

        Crop::create($validated);

        return redirect()->route('crops.index')
            ->with('success', 'Crop created successfully.');
    }

    public function show(Crop $crop)
    {
        // The cycle table reads dates, budget and booked cost per row.
        $crop->load('cropCycles.block.farm', 'cropCycles.seasonalBudget', 'cropCycles.costAllocations');
        return view('crops.show', compact('crop'));
    }

    public function edit(Crop $crop)
    {
        return view('crops.edit', compact('crop'));
    }

    public function update(Request $request, Crop $crop)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'variety' => 'nullable|string|max:255',
            'crop_type' => 'required|string|max:255',
            'days_to_maturity' => 'nullable|integer|min:1',
            'expected_yield_per_acre' => 'nullable|numeric|min:0',
            'seeds_per_bed' => 'nullable|integer|min:0',
            'expected_yield_per_bed_kg' => 'nullable|numeric|min:0',
            'reference_price_per_kg' => 'nullable|numeric|min:0',
            'expected_germination_rate' => 'nullable|numeric|min:0|max:1',
            'default_labour_budget' => 'nullable|numeric|min:0',
            'default_input_budget' => 'nullable|numeric|min:0',
            'default_irrigation_budget' => 'nullable|numeric|min:0',
            'default_overhead_budget' => 'nullable|numeric|min:0',
        ]);

        $crop->update($validated);

        return redirect()->route('crops.index')
            ->with('success', 'Crop updated successfully.');
    }

    public function destroy(Crop $crop)
    {
        $crop->delete();

        return redirect()->route('crops.index')
            ->with('success', 'Crop deleted successfully.');
    }
}
