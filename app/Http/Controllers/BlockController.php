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

        Block::create($validated);

        return redirect()->route('blocks.index')
            ->with('success', 'Block created successfully.');
    }

    public function show(Block $block)
    {
        $block->load('farm', 'cropCycles.crop');
        return view('blocks.show', compact('block'));
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
