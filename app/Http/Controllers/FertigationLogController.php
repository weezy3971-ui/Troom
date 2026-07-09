<?php

namespace App\Http\Controllers;

use App\Models\CostAllocation;
use App\Models\CropCycle;
use App\Models\FertigationLog;
use Illuminate\Http\Request;

class FertigationLogController extends Controller
{
    public function index(Request $request)
    {
        $query = FertigationLog::with('cropCycle.block', 'cropCycle.crop', 'logger')->latest('log_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nutrient_type', 'like', "%{$search}%")
                  ->orWhere('method', 'like', "%{$search}%")
                  ->orWhereHas('cropCycle', fn($c) => $c->where('season_name', 'like', "%{$search}%"))
                  ->orWhereHas('cropCycle.block', fn($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        $logs = $query->get();
        return view('fertigation-logs.index', compact('logs', 'search'));
    }

    public function create()
    {
        $cropCycles = CropCycle::with('block', 'crop')->orderBy('season_name')->get();
        return view('fertigation-logs.create', compact('cropCycles'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateLog($request);
        $validated['logged_by'] = $request->user()?->id;

        $log = FertigationLog::create($validated);
        $this->syncCostAllocation($log);

        return redirect()->route('fertigation-logs.index')
            ->with('success', 'Fertigation logged and cost allocated.');
    }

    public function show(FertigationLog $fertigationLog)
    {
        $fertigationLog->load('cropCycle.block', 'cropCycle.crop', 'logger');
        return view('fertigation-logs.show', compact('fertigationLog'));
    }

    public function edit(FertigationLog $fertigationLog)
    {
        $cropCycles = CropCycle::with('block', 'crop')->orderBy('season_name')->get();
        return view('fertigation-logs.edit', compact('fertigationLog', 'cropCycles'));
    }

    public function update(Request $request, FertigationLog $fertigationLog)
    {
        $validated = $this->validateLog($request);

        $fertigationLog->update($validated);
        $this->syncCostAllocation($fertigationLog);

        return redirect()->route('fertigation-logs.show', $fertigationLog)
            ->with('success', 'Fertigation log updated.');
    }

    public function destroy(FertigationLog $fertigationLog)
    {
        CostAllocation::where('source_type', 'fertigation')
            ->where('source_id', $fertigationLog->id)
            ->delete();

        $fertigationLog->delete();

        return redirect()->route('fertigation-logs.index')
            ->with('success', 'Fertigation log deleted.');
    }

    private function validateLog(Request $request): array
    {
        return $request->validate([
            'crop_cycle_id' => 'required|exists:crop_cycles,id',
            'log_date' => 'required|date',
            'nutrient_type' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'method' => 'nullable|string|max:255',
            'cost' => 'required|numeric|min:0',
        ]);
    }

    /**
     * Business rule: every fertigation log generates a Cost Allocation row
     * (source type: fertigation).
     */
    private function syncCostAllocation(FertigationLog $log): void
    {
        CostAllocation::updateOrCreate(
            ['source_type' => 'fertigation', 'source_id' => $log->id],
            [
                'crop_cycle_id' => $log->crop_cycle_id,
                'block_id' => $log->cropCycle?->block_id,
                'amount' => $log->cost,
                'allocation_date' => $log->log_date,
                'description' => "Fertigation: {$log->nutrient_type}",
            ]
        );
    }
}
