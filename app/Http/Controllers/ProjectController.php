<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\CostAllocation;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TaskAssignment;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with('farm')->withCount('tasks')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $projects = $query->get();
        return view('projects.index', compact('projects', 'search'));
    }

    public function create()
    {
        return view('projects.create', $this->formData());
    }

    public function store(Request $request)
    {
        Project::create($this->validateProject($request));

        return redirect()->route('projects.index')
            ->with('success', 'Project created.');
    }

    public function show(Project $project)
    {
        $project->load([
            'farm', 'block', 'cropCycle',
            'tasks.assignments.worker',
            'costAllocations',
        ]);

        // Spend rolled up by source type ("where the money is going").
        $spendByType = $project->costAllocations
            ->groupBy('source_type')
            ->map(fn ($rows) => (float) $rows->sum('amount'));

        $totalSpend = (float) $project->costAllocations->sum('amount');

        // Inputs/materials consumed against this project (issue transactions).
        $inputs = InventoryTransaction::with('inventoryItem')
            ->where('project_id', $project->id)
            ->where('type', 'issue')
            ->latest('transaction_date')
            ->get();

        $inventoryItems = InventoryItem::orderBy('name')->get();

        return view('projects.show', compact('project', 'spendByType', 'totalSpend', 'inputs', 'inventoryItems'));
    }

    public function edit(Project $project)
    {
        return view('projects.edit', array_merge(['project' => $project], $this->formData()));
    }

    public function update(Request $request, Project $project)
    {
        $project->update($this->validateProject($request, $project));

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        // Assignment cost rows are tagged by project_id; clear them too.
        CostAllocation::where('project_id', $project->id)->delete();
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted.');
    }

    // ---- Tasks (splitting work between labourers) ----

    public function storeTask(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->tasks()->create($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Task added.');
    }

    public function destroyTask(Project $project, ProjectTask $task)
    {
        abort_unless($task->project_id === $project->id, 404);

        // Remove the cost rows for this task's assignments.
        $assignmentIds = $task->assignments()->pluck('id');
        CostAllocation::where('source_type', 'assignment')
            ->whereIn('source_id', $assignmentIds)
            ->delete();

        $task->delete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Task removed.');
    }

    // ---- Assignments (labour assigned to a task) ----

    public function storeAssignment(Request $request, Project $project, ProjectTask $task)
    {
        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'assigned_date' => 'required|date',
            'hours' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $validated['cost'] = $validated['hours'] * $validated['rate'];

        $assignment = $task->assignments()->create($validated);
        $this->syncCostAllocation($project, $task, $assignment);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Worker assigned.');
    }

    public function destroyAssignment(Project $project, ProjectTask $task, TaskAssignment $assignment)
    {
        abort_unless($task->project_id === $project->id && $assignment->project_task_id === $task->id, 404);

        CostAllocation::where('source_type', 'assignment')
            ->where('source_id', $assignment->id)
            ->delete();

        $assignment->delete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Assignment removed.');
    }

    // ---- Inputs / materials consumed against the project ----

    public function storeInput(Request $request, Project $project)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01',
            'cost' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
        ]);

        $item = InventoryItem::with('transactions')->findOrFail($validated['inventory_item_id']);

        // Guard: can't issue more than is in stock (mirrors Inventory module).
        $stock = $item->currentStock();
        if ((float) $validated['quantity'] > $stock) {
            throw ValidationException::withMessages([
                'quantity' => "Cannot issue {$validated['quantity']} {$item->unit}: only {$stock} in stock.",
            ]);
        }

        $transaction = InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'farm_id' => $project->farm_id,
            'crop_cycle_id' => $project->crop_cycle_id,
            'project_id' => $project->id,
            'type' => 'issue',
            'quantity' => $validated['quantity'],
            'transaction_date' => $validated['transaction_date'],
            'reference' => "Project: {$project->code}",
            'cost' => $validated['cost'],
        ]);

        CostAllocation::updateOrCreate(
            ['source_type' => 'input', 'source_id' => $transaction->id],
            [
                'project_id' => $project->id,
                'crop_cycle_id' => $project->crop_cycle_id,
                'block_id' => $project->block_id,
                'amount' => $transaction->cost,
                'allocation_date' => $transaction->transaction_date,
                'description' => "Input: {$item->name} ({$transaction->quantity} {$item->unit})",
            ]
        );

        return redirect()->route('projects.show', $project)
            ->with('success', 'Input consumption recorded.');
    }

    public function destroyInput(Project $project, InventoryTransaction $transaction)
    {
        abort_unless($transaction->project_id === $project->id, 404);

        CostAllocation::where('source_type', 'input')
            ->where('source_id', $transaction->id)
            ->delete();

        // Deleting the issue transaction returns the quantity to stock.
        $transaction->delete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Input consumption removed and stock restored.');
    }

    /**
     * Every assignment writes a Cost Allocation row tagged to the project, so
     * labour cost rolls into "where the money is going".
     */
    private function syncCostAllocation(Project $project, ProjectTask $task, TaskAssignment $assignment): void
    {
        $assignment->loadMissing('worker');

        CostAllocation::updateOrCreate(
            ['source_type' => 'assignment', 'source_id' => $assignment->id],
            [
                'project_id' => $project->id,
                'crop_cycle_id' => $project->crop_cycle_id,
                'block_id' => $project->block_id,
                'amount' => $assignment->cost,
                'allocation_date' => $assignment->assigned_date,
                'description' => "Labour: {$task->name} ({$assignment->worker->name})",
            ]
        );
    }

    private function formData(): array
    {
        return [
            'farms' => Farm::orderBy('name')->get(),
            'blocks' => Block::with('farm')->orderBy('name')->get(),
            'cropCycles' => CropCycle::with('block')->orderBy('season_name')->get(),
            'workers' => Worker::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validateProject(Request $request, ?Project $project = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:projects,code' . ($project ? ",{$project->id}" : ''),
            'project_type' => 'required|in:construction,land_prep,training,maintenance,other',
            'description' => 'nullable|string',
            'status' => 'required|in:planned,active,completed,cancelled',
            'farm_id' => 'nullable|exists:farms,id',
            'block_id' => 'nullable|exists:blocks,id',
            'crop_cycle_id' => 'nullable|exists:crop_cycles,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'required|numeric|min:0',
        ]);
    }
}
