<?php

namespace App\Http\Controllers;

use App\Models\CropCycle;
use App\Models\GerminationCheck;
use App\Models\PlantPopulationCount;
use App\Models\YieldForecast;
use Illuminate\Http\Request;

/**
 * In-season crop monitoring records that refine a cycle's yield projection:
 * germination checks, plant-population (stand) counts, and pre-harvest yield
 * sampling. All are surfaced on, and managed from, the crop-cycle page.
 */
class CropMonitoringController extends Controller
{
    // ---- Germination checks ----

    public function storeGermination(Request $request, CropCycle $cropCycle)
    {
        $validated = $request->validate([
            'check_date' => 'required|date',
            'days_after_sowing' => 'nullable|integer|min:0',
            'sample_size' => 'required|integer|min:1',
            'germinated_count' => 'required|integer|min:0|lte:sample_size',
            'notes' => 'nullable|string|max:255',
        ]);

        $validated['germination_rate'] = round($validated['germinated_count'] / $validated['sample_size'], 3);
        $validated['recorded_by'] = auth()->id();

        $cropCycle->germinationChecks()->create($validated);

        return back()->with('success', 'Germination check recorded.');
    }

    public function destroyGermination(CropCycle $cropCycle, GerminationCheck $germinationCheck)
    {
        abort_unless($germinationCheck->crop_cycle_id === $cropCycle->id, 404);
        $germinationCheck->delete();

        return back()->with('success', 'Germination check removed.');
    }

    // ---- Plant-population (stand) counts ----

    public function storePopulation(Request $request, CropCycle $cropCycle)
    {
        $validated = $request->validate([
            'count_date' => 'required|date',
            'days_after_planting' => 'nullable|integer|min:0',
            'population_pct' => 'required|numeric|min:0|max:100',
            'sample_bed_count' => 'nullable|integer|min:0',
            'plants_counted' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $cropCycle->plantPopulationCounts()->create([
            'count_date' => $validated['count_date'],
            'days_after_planting' => $validated['days_after_planting'] ?? null,
            'population_rate' => round($validated['population_pct'] / 100, 3),
            'sample_bed_count' => $validated['sample_bed_count'] ?? null,
            'plants_counted' => $validated['plants_counted'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Plant population count recorded.');
    }

    public function destroyPopulation(CropCycle $cropCycle, PlantPopulationCount $plantPopulationCount)
    {
        abort_unless($plantPopulationCount->crop_cycle_id === $cropCycle->id, 404);
        $plantPopulationCount->delete();

        return back()->with('success', 'Population count removed.');
    }

    // ---- Pre-harvest yield forecast (sampling) ----

    public function storeForecast(Request $request, CropCycle $cropCycle)
    {
        $validated = $request->validate([
            'forecast_date' => 'required|date',
            'sample_bed_count' => 'required|integer|min:1',
            'total_bed_count' => 'required|integer|min:1',
            'sample_yield_kg' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $validated['projected_total_kg'] = round(
            $validated['sample_yield_kg'] / $validated['sample_bed_count'] * $validated['total_bed_count'],
            2
        );
        $validated['recorded_by'] = auth()->id();

        $cropCycle->yieldForecasts()->create($validated);

        return back()->with('success', 'Pre-harvest forecast recorded.');
    }

    public function destroyForecast(CropCycle $cropCycle, YieldForecast $yieldForecast)
    {
        abort_unless($yieldForecast->crop_cycle_id === $cropCycle->id, 404);
        $yieldForecast->delete();

        return back()->with('success', 'Forecast removed.');
    }
}
