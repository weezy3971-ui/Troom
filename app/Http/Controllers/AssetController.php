<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Farm;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::with('farm');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('farm', fn($f) => $f->where('name', 'like', "%{$search}%"));
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $assets = $query->get();
        return view('assets.index', compact('assets', 'search'));
    }

    public function create()
    {
        $farms = Farm::orderBy('name')->get();
        return view('assets.create', compact('farms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:pump,vehicle,equipment',
            'purchase_date' => 'nullable|date',
            'status' => 'required|in:operational,maintenance,down',
        ]);

        Asset::create($validated);

        return redirect()->route('assets.index')
            ->with('success', 'Asset registered successfully.');
    }

    public function show(Asset $asset)
    {
        $asset->load('farm', 'statusHistories.changedByUser');
        return view('assets.show', compact('asset'));
    }

    public function edit(Asset $asset)
    {
        $farms = Farm::orderBy('name')->get();
        return view('assets.edit', compact('asset', 'farms'));
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:pump,vehicle,equipment',
            'purchase_date' => 'nullable|date',
            'status' => 'required|in:operational,maintenance,down',
        ]);

        // Use the audit-trail method for status changes
        if ($validated['status'] !== $asset->status) {
            $asset->updateStatus($validated['status'], $request->user()?->id);
            unset($validated['status']);
        }

        $asset->update($validated);

        return redirect()->route('assets.index')
            ->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();

        return redirect()->route('assets.index')
            ->with('success', 'Asset decommissioned successfully.');
    }
}
