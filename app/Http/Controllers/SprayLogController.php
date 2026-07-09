<?php

namespace App\Http\Controllers;

use App\Models\CropCycle;
use App\Models\SprayLog;
use App\Models\User;
use Illuminate\Http\Request;

class SprayLogController extends Controller
{
    public function index(Request $request)
    {
        $query = SprayLog::with('cropCycle.block', 'cropCycle.crop', 'applicator')->latest('log_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('chemical_used', 'like', "%{$search}%")
                  ->orWhere('target_pest', 'like', "%{$search}%")
                  ->orWhereHas('cropCycle', fn($c) => $c->where('season_name', 'like', "%{$search}%"))
                  ->orWhereHas('cropCycle.block', fn($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        $logs = $query->get();
        return view('spray-logs.index', compact('logs', 'search'));
    }

    public function create()
    {
        $cropCycles = CropCycle::with('block', 'crop')->orderBy('season_name')->get();
        $applicators = User::orderBy('name')->get();
        return view('spray-logs.create', compact('cropCycles', 'applicators'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateLog($request);

        SprayLog::create($validated);

        return redirect()->route('spray-logs.index')
            ->with('success', 'Spray logged successfully.');
    }

    public function show(SprayLog $sprayLog)
    {
        $sprayLog->load('cropCycle.block', 'cropCycle.crop', 'applicator');
        return view('spray-logs.show', compact('sprayLog'));
    }

    public function edit(SprayLog $sprayLog)
    {
        $cropCycles = CropCycle::with('block', 'crop')->orderBy('season_name')->get();
        $applicators = User::orderBy('name')->get();
        return view('spray-logs.edit', compact('sprayLog', 'cropCycles', 'applicators'));
    }

    public function update(Request $request, SprayLog $sprayLog)
    {
        $validated = $this->validateLog($request);

        $sprayLog->update($validated);

        return redirect()->route('spray-logs.show', $sprayLog)
            ->with('success', 'Spray log updated successfully.');
    }

    public function destroy(SprayLog $sprayLog)
    {
        $sprayLog->delete();

        return redirect()->route('spray-logs.index')
            ->with('success', 'Spray log deleted successfully.');
    }

    private function validateLog(Request $request): array
    {
        return $request->validate([
            'crop_cycle_id' => 'required|exists:crop_cycles,id',
            'log_date' => 'required|date',
            'chemical_used' => 'required|string|max:255',
            'target_pest' => 'nullable|string|max:255',
            'quantity' => 'nullable|numeric|min:0',
            'pre_harvest_interval_days' => 'required|integer|min:0',
            'applicator_id' => 'nullable|exists:users,id',
            'photo_path' => 'nullable|string|max:255',
        ]);
    }
}
