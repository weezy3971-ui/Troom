<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Models\WorkerAttendance;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkerAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'onsite');

        $query = WorkerAttendance::with(['worker', 'recordedBy'])->latest('checked_in_at');

        if ($filter === 'onsite') {
            $query->onSite();
        }

        if ($search = $request->input('search')) {
            $query->whereHas('worker', fn ($w) => $w->where('name', 'like', "%{$search}%"));
        }

        $attendances = $query->get();

        return view('worker-attendances.index', compact('attendances', 'search', 'filter'));
    }

    public function create()
    {
        $workers = Worker::where('is_active', true)->orderBy('name')->get();

        return view('worker-attendances.create', compact('workers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'checked_in_at' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $worker = Worker::findOrFail($validated['worker_id']);

        // Guard: a worker can't be checked in twice at once.
        $open = WorkerAttendance::where('worker_id', $worker->id)->whereNull('checked_out_at')->exists();
        if ($open) {
            throw ValidationException::withMessages([
                'worker_id' => "{$worker->name} is already checked in. Check them out first.",
            ]);
        }

        WorkerAttendance::create([
            'worker_id' => $worker->id,
            'work_date' => \Illuminate\Support\Carbon::parse($validated['checked_in_at'])->toDateString(),
            'checked_in_at' => $validated['checked_in_at'],
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => $request->user()?->id,
        ]);

        return redirect()->route('worker-attendances.index')
            ->with('success', "{$worker->name} checked in.");
    }

    public function checkout(Request $request, WorkerAttendance $workerAttendance)
    {
        if ($workerAttendance->isCheckedOut()) {
            return redirect()->route('worker-attendances.index')
                ->with('error', 'This worker was already checked out.');
        }

        $workerAttendance->update([
            'checked_out_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', "{$workerAttendance->worker->name} checked out.");
    }

    public function destroy(WorkerAttendance $workerAttendance)
    {
        $workerAttendance->delete();

        return redirect()->route('worker-attendances.index')
            ->with('success', 'Attendance record deleted.');
    }
}
