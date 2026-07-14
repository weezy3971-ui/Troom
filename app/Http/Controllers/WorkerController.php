<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    public function index(Request $request)
    {
        $query = Worker::withCount('assignments')->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        }

        $workers = $query->get();
        return view('workers.index', compact('workers', 'search'));
    }

    public function create()
    {
        return view('workers.create');
    }

    public function store(Request $request)
    {
        Worker::create($this->validateWorker($request));

        return redirect()->route('workers.index')
            ->with('success', 'Worker added.');
    }

    public function edit(Worker $worker)
    {
        return view('workers.edit', compact('worker'));
    }

    public function update(Request $request, Worker $worker)
    {
        $worker->update($this->validateWorker($request));

        return redirect()->route('workers.index')
            ->with('success', 'Worker updated.');
    }

    public function destroy(Worker $worker)
    {
        $worker->delete();

        return redirect()->route('workers.index')
            ->with('success', 'Worker removed.');
    }

    private function validateWorker(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'worker_type' => 'required|in:casual,permanent',
            'national_id' => 'nullable|string|max:50',
            'employee_no' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'pay_phone' => 'nullable|string|max:50',
            'default_rate' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
