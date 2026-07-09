<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\CostAllocation;
use App\Models\CropCycle;
use App\Models\LabourAttendance;
use Illuminate\Http\Request;

class LabourAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = LabourAttendance::with('block.farm', 'cropCycle')->latest('attendance_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('worker_name', 'like', "%{$search}%")
                  ->orWhere('task', 'like', "%{$search}%")
                  ->orWhereHas('block', fn($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        $attendances = $query->get();
        return view('labour-attendances.index', compact('attendances', 'search'));
    }

    public function create()
    {
        $blocks = Block::with('farm')->orderBy('name')->get();
        $cropCycles = CropCycle::with('block')->orderBy('season_name')->get();
        return view('labour-attendances.create', compact('blocks', 'cropCycles'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateAttendance($request);
        $validated['cost'] = $validated['hours_worked'] * $validated['rate'];

        $attendance = LabourAttendance::create($validated);
        $this->syncCostAllocation($attendance);

        return redirect()->route('labour-attendances.index')
            ->with('success', 'Labour attendance recorded and cost allocated.');
    }

    public function show(LabourAttendance $labourAttendance)
    {
        $labourAttendance->load('block.farm', 'cropCycle.crop');
        return view('labour-attendances.show', compact('labourAttendance'));
    }

    public function edit(LabourAttendance $labourAttendance)
    {
        $blocks = Block::with('farm')->orderBy('name')->get();
        $cropCycles = CropCycle::with('block')->orderBy('season_name')->get();
        return view('labour-attendances.edit', compact('labourAttendance', 'blocks', 'cropCycles'));
    }

    public function update(Request $request, LabourAttendance $labourAttendance)
    {
        $validated = $this->validateAttendance($request);
        $validated['cost'] = $validated['hours_worked'] * $validated['rate'];

        $labourAttendance->update($validated);
        $this->syncCostAllocation($labourAttendance);

        return redirect()->route('labour-attendances.show', $labourAttendance)
            ->with('success', 'Labour attendance updated.');
    }

    public function destroy(LabourAttendance $labourAttendance)
    {
        CostAllocation::where('source_type', 'labour')
            ->where('source_id', $labourAttendance->id)
            ->delete();

        $labourAttendance->delete();

        return redirect()->route('labour-attendances.index')
            ->with('success', 'Labour attendance deleted.');
    }

    private function validateAttendance(Request $request): array
    {
        return $request->validate([
            'attendance_date' => 'required|date',
            'worker_name' => 'required|string|max:255',
            'block_id' => 'nullable|exists:blocks,id',
            'crop_cycle_id' => 'nullable|exists:crop_cycles,id',
            'task' => 'required|string|max:255',
            'hours_worked' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
        ]);
    }

    /**
     * Business rule: every attendance entry generates a Cost Allocation row
     * (source type: labour).
     */
    private function syncCostAllocation(LabourAttendance $attendance): void
    {
        CostAllocation::updateOrCreate(
            ['source_type' => 'labour', 'source_id' => $attendance->id],
            [
                'crop_cycle_id' => $attendance->crop_cycle_id,
                'block_id' => $attendance->block_id,
                'amount' => $attendance->cost,
                'allocation_date' => $attendance->attendance_date,
                'description' => "Labour: {$attendance->task} ({$attendance->worker_name})",
            ]
        );
    }
}
