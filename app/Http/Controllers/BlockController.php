<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Farm;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function index(Request $request)
    {
        $query = Block::with('farm')->withCount('cropCycles');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('soil_type', 'like', "%{$search}%")
                  ->orWhereHas('farm', fn($f) => $f->where('name', 'like', "%{$search}%"));
            });
        }

        $blocks = $query->get();
        return view('blocks.index', compact('blocks', 'search'));
    }

    public function create()
    {
        $farms = Farm::orderBy('name')->get();
        return view('blocks.create', compact('farms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'size_acres' => 'required|numeric|min:0.01',
            'soil_type' => 'nullable|string|max:255',
        ]);

        $block = Block::create($validated);

        // The next thing that happens to a new block is that it gets prepared,
        // so the flow leads there rather than back to the list.
        return redirect()->route('land-preparations.open', $block)
            ->with('success', "{$block->name} created. Next: prepare the block.");
    }

    public function show(Block $block)
    {
        // Consolidated block hub: everything happening on this block in one place.
        $block->load([
            'farm',
            'cropCycles.crop',
            'irrigationLogs' => fn($q) => $q->latest('log_date')->limit(15),
            'irrigationLogs.pump',
            'fertigationLogs' => fn($q) => $q->latest('log_date')->limit(15),
            'sprayLogs' => fn($q) => $q->latest('log_date')->limit(15),
            'harvestBatches' => fn($q) => $q->latest('harvest_date')->limit(15),
            'harvestBatches.cropCycle.crop',
            'labourAttendances' => fn($q) => $q->latest('attendance_date')->limit(15),
            'dailyActivities' => fn($q) => $q->latest('activity_date')->limit(15),
        ]);

        // Totals shown as tab counters (independent of the 15-row display cap).
        $counts = [
            'cycles' => $block->cropCycles->count(),
            'irrigation' => $block->irrigationLogs()->count(),
            'fertigation' => $block->fertigationLogs()->count(),
            'spray' => $block->sprayLogs()->count(),
            'harvest' => $block->harvestBatches()->count(),
            'labour' => $block->labourAttendances()->count(),
            'activity' => $block->dailyActivities()->count(),
        ];

        return view('blocks.show', compact('block', 'counts'));
    }

    public function edit(Block $block)
    {
        $farms = Farm::orderBy('name')->get();
        return view('blocks.edit', compact('block', 'farms'));
    }

    public function update(Request $request, Block $block)
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'size_acres' => 'required|numeric|min:0.01',
            'soil_type' => 'nullable|string|max:255',
        ]);

        $block->update($validated);

        return redirect()->route('blocks.index')
            ->with('success', 'Block updated successfully.');
    }

    public function destroy(Block $block)
    {
        // Business rule: block cannot be deleted while linked to active crop cycle
        if ($block->hasActiveCropCycle()) {
            return redirect()->route('blocks.index')
                ->with('error', 'Cannot delete a block with an active crop cycle.');
        }

        $block->delete();

        return redirect()->route('blocks.index')
            ->with('success', 'Block deleted successfully.');
    }
}
