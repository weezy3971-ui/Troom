<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * The task list the reminder engine feeds. Staff see what is due on them
 * today; managers see everything and can reassign.
 */
class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $request->input('scope', 'mine');

        $query = Task::with(['assignee', 'cropCycle.block', 'schedulePoint'])
            ->orderByRaw('due_date is null')
            ->orderBy('due_date');

        if ($scope === 'mine') {
            $query->where('assigned_to', $user->id);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'pending');
        }

        $tasks = $query->get();

        $overdue = $tasks->filter(fn (Task $t) => $t->isOverdue());
        $today = $tasks->filter(fn (Task $t) => $t->due_date?->isToday() ?? false);
        $upcoming = $tasks->filter(fn (Task $t) => $t->due_date?->isFuture() ?? true);

        return view('tasks.index', compact('tasks', 'overdue', 'today', 'upcoming', 'scope'));
    }

    /**
     * Mark done without logging an activity — for tasks that carry no cost or
     * product. Scheduled spray/input work should be closed by logging the
     * activity against the cycle instead, so the cost is captured.
     */
    public function complete(Request $request, Task $task)
    {
        $this->authorizeTask($request, $task);

        $task->update(['status' => 'done', 'completed_at' => now()]);

        return back()->with('success', 'Task marked done.');
    }

    public function reassign(Request $request, Task $task)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $task->update($validated);

        $name = User::find($validated['assigned_to'])?->name;

        return back()->with('success', "Task reassigned to {$name}.");
    }

    /**
     * A task can be closed by its assignee, or by anyone who can manage field
     * operations.
     */
    private function authorizeTask(Request $request, Task $task): void
    {
        $user = $request->user();

        abort_unless(
            $task->assigned_to === $user->id
                || \App\Support\ModuleAccess::allows($user, 'daily_ops'),
            403
        );
    }
}
