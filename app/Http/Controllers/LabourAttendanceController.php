<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\CostAllocation;
use App\Models\CropCycle;
use App\Models\LabourAttendance;
use App\Models\Worker;
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
        $workers = Worker::where('is_active', true)->orderBy('name')->get();
        return view('labour-attendances.create', compact('blocks', 'cropCycles', 'workers'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateAttendance($request);
        $validated = $this->normalise($validated);

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
        $workers = Worker::where('is_active', true)->orderBy('name')->get();
        return view('labour-attendances.edit', compact('labourAttendance', 'blocks', 'cropCycles', 'workers'));
    }

    public function update(Request $request, LabourAttendance $labourAttendance)
    {
        $validated = $this->validateAttendance($request);
        $validated = $this->normalise($validated);

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
            'worker_id' => 'nullable|exists:workers,id',
            'worker_name' => 'required|string|max:255',
            'worker_type' => 'nullable|in:casual,permanent',
            'worker_phone' => 'nullable|string|max:50',
            'worker_national_id' => 'nullable|string|max:50',
            'block_id' => 'nullable|exists:blocks,id',
            'crop_cycle_id' => 'nullable|exists:crop_cycles,id',
            'task' => 'required|string|max:255',
            'pay_basis' => 'required|in:hourly,target',
            // Hourly fields. Hours can be entered directly OR derived from an
            // optional check-in / check-out pair (single place to log time).
            'hours_worked' => 'nullable|numeric|min:0',
            'rate' => 'required_if:pay_basis,hourly|nullable|numeric|min:0',
            'checked_in_at' => 'nullable|date',
            'checked_out_at' => 'nullable|date|after_or_equal:checked_in_at',
            // Target / piece-rate fields ("5 beds = 600 KES"). Unit is free text
            // so the supervisor can enter whatever they pay by (beds, crates, kg…).
            'target_unit' => 'required_if:pay_basis,target|nullable|string|max:50',
            'target_qty' => 'nullable|numeric|min:0',
            'qty_completed' => 'required_if:pay_basis,target|nullable|numeric|min:0',
            'rate_per_unit' => 'required_if:pay_basis,target|nullable|numeric|min:0',
        ]);
    }

    /**
     * Fill the unused side of the entry with 0 and compute the cost from the
     * chosen pay basis (hours × rate, or qty × rate_per_unit).
     */
    private function normalise(array $validated): array
    {
        // When a roster worker is chosen, borrow their type / phone / ID for any
        // fields left blank (a manually typed value still wins).
        if (! empty($validated['worker_id'])) {
            $worker = Worker::find($validated['worker_id']);
            if ($worker) {
                $validated['worker_name'] = $validated['worker_name'] ?: $worker->name;
                $validated['worker_type'] = $validated['worker_type'] ?? $worker->worker_type;
                $validated['worker_phone'] = $validated['worker_phone'] ?? $worker->phone;
                $validated['worker_national_id'] = $validated['worker_national_id'] ?? $worker->national_id;
            }
        }


        if (($validated['pay_basis'] ?? 'hourly') === 'target') {
            $validated['hours_worked'] = 0;
            $validated['rate'] = 0;
            $validated['checked_in_at'] = null;
            $validated['checked_out_at'] = null;
            $validated['cost'] = (float) ($validated['qty_completed'] ?? 0) * (float) ($validated['rate_per_unit'] ?? 0);
        } else {
            $validated['target_unit'] = null;
            $validated['target_qty'] = null;
            $validated['qty_completed'] = null;
            $validated['rate_per_unit'] = null;

            // If check-in/out are both given, they are the source of truth for hours.
            if (! empty($validated['checked_in_at']) && ! empty($validated['checked_out_at'])) {
                $in = \Illuminate\Support\Carbon::parse($validated['checked_in_at']);
                $out = \Illuminate\Support\Carbon::parse($validated['checked_out_at']);
                $validated['hours_worked'] = round($in->floatDiffInHours($out), 2);
            }

            $validated['cost'] = (float) ($validated['hours_worked'] ?? 0) * (float) ($validated['rate'] ?? 0);
        }

        return $validated;
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
