<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    public function index(Request $request)
    {
        $query = Farm::withCount('blocks', 'assets');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $farms = $query->get();
        return view('farms.index', compact('farms', 'search'));
    }

    /**
     * Map view: farms plotted from their coordinates, coloured by whether they
     * have an active crop cycle, with block/asset counts in the popup.
     */
    public function map()
    {
        $farms = Farm::withCount('blocks', 'assets')
            ->with(['blocks' => fn($q) => $q->withCount(['cropCycles as active_cycles_count' => fn($c) => $c->where('status', 'active')])])
            ->get()
            ->map(function ($farm) {
                $activeCycles = $farm->blocks->sum('active_cycles_count');
                return [
                    'id' => $farm->id,
                    'name' => $farm->name,
                    'location' => $farm->location,
                    'lat' => (float) $farm->latitude,
                    'lng' => (float) $farm->longitude,
                    'blocks' => $farm->blocks_count,
                    'assets' => $farm->assets_count,
                    'active_cycles' => $activeCycles,
                    'url' => route('farms.show', $farm),
                ];
            })
            ->filter(fn($f) => $f['lat'] && $f['lng'])
            ->values();

        return view('farms.map', compact('farms'));
    }

    public function create()
    {
        return view('farms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'size_acres' => 'required|numeric|min:0.01',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        Farm::create($validated);

        return redirect()->route('farms.index')
            ->with('success', 'Farm created successfully.');
    }

    public function show(Farm $farm)
    {
        $farm->load('blocks', 'assets');
        return view('farms.show', compact('farm'));
    }

    public function edit(Farm $farm)
    {
        return view('farms.edit', compact('farm'));
    }

    public function update(Request $request, Farm $farm)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'size_acres' => 'required|numeric|min:0.01',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $farm->update($validated);

        return redirect()->route('farms.index')
            ->with('success', 'Farm updated successfully.');
    }

    public function destroy(Farm $farm)
    {
        // Business rule: cannot delete a farm that still has blocks linked to it.
        $blockCount = $farm->blocks()->count();
        if ($blockCount > 0) {
            return redirect()->route('farms.index')
                ->with('error', "Cannot delete \"{$farm->name}\": {$blockCount} block(s) are still linked to it. Remove or reassign them first.");
        }

        $farm->delete();

        return redirect()->route('farms.index')
            ->with('success', 'Farm deleted successfully.');
    }
}
