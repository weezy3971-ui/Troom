@extends('layouts.app')
@section('title', $project->name)

@php
    $statusBadge = [
        'planned' => 'badge-planned', 'active' => 'badge-active',
        'completed' => 'badge-completed', 'cancelled' => 'badge-cancelled',
    ];
    $taskBadge = [
        'pending' => 'badge-planned', 'in_progress' => 'badge-growing', 'done' => 'badge-active',
    ];
    $sourceLabel = [
        'assignment' => 'Labour', 'labour' => 'Labour', 'input' => 'Inputs / Materials',
    ];
    $budget = (float) $project->budget;
    $pct = $budget > 0 ? min(100, round($totalSpend / $budget * 100)) : 0;
    $workers = \App\Models\Worker::where('is_active', true)->orderBy('name')->get();
@endphp

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('projects.index') }}">Projects</a> <span>/</span> <span>{{ $project->name }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $project->name }}
            <span class="badge {{ $statusBadge[$project->status] ?? 'badge-neutral' }}" style="vertical-align: middle; margin-left: 8px;">{{ ucfirst($project->status) }}</span>
        </h1>
        <p class="page-subtitle">
            <span style="font-family: var(--font-mono);">{{ $project->code }}</span>
            @if($project->farm) · {{ $project->farm->name }} @endif
            @if($project->block) / {{ $project->block->name }} @endif
        </p>
    </div>
    <div class="actions">
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-secondary">Edit</a>
    </div>
</div>

@if($project->description)
    <div class="card" style="margin-bottom: 20px;"><p style="color: var(--text-secondary);">{{ $project->description }}</p></div>
@endif

{{-- ---- Spend roll-up: "where the money is going" ---- --}}
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header"><h3 class="card-title">Spend vs Budget</h3></div>
    <div class="detail-grid" style="margin-bottom: 16px;">
        <div class="detail-item">
            <div class="detail-label">Budget</div>
            <div class="detail-value">KES {{ number_format($budget) }}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Total Spent</div>
            <div class="detail-value" style="{{ $budget > 0 && $totalSpend > $budget ? 'color: var(--danger);' : '' }}">KES {{ number_format($totalSpend) }}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Remaining</div>
            <div class="detail-value">KES {{ number_format($budget - $totalSpend) }}</div>
        </div>
    </div>

    <div style="height: 10px; background: var(--bg-secondary); border-radius: 6px; overflow: hidden; margin-bottom: 8px;">
        <div style="height: 100%; width: {{ $pct }}%; background: {{ $totalSpend > $budget && $budget > 0 ? 'var(--danger)' : 'var(--olive)' }};"></div>
    </div>
    <p class="page-subtitle" style="margin: 0;">{{ $pct }}% of budget used</p>

    @if($spendByType->isNotEmpty())
        <div style="margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap;">
            @foreach($spendByType as $type => $amount)
                <span class="kpi-chip">{{ $sourceLabel[$type] ?? ucfirst($type) }}: <strong>KES {{ number_format($amount) }}</strong></span>
            @endforeach
        </div>
    @endif
</div>

{{-- ---- Inputs / materials consumed ---- --}}
<div class="page-header">
    <h3 class="card-title" style="font-size: 17px;">Inputs &amp; Materials</h3>
</div>

<div class="card" style="margin-bottom: 24px;">
    @if($inputs->isNotEmpty())
        <div class="table-wrap" style="margin-bottom: 14px;">
            <table>
                <thead>
                    <tr><th>Item</th><th>Date</th><th>Quantity</th><th>Cost (KES)</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($inputs as $in)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $in->inventoryItem?->name ?? '—' }}</td>
                        <td>{{ $in->transaction_date->format('M d, Y') }}</td>
                        <td>{{ number_format($in->quantity, 2) }} {{ $in->inventoryItem?->unit }}</td>
                        <td>{{ number_format($in->cost) }}</td>
                        <td>
                            <form action="{{ route('projects.inputs.destroy', [$project, $in]) }}" method="POST" data-confirm="Remove this input and restore stock?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($inventoryItems->isEmpty())
        <div class="alert alert-warning" style="margin: 0;">No inventory items exist yet. Add items in the Inventory module to consume them here.</div>
    @else
        <form action="{{ route('projects.inputs.store', $project) }}" method="POST" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            @csrf
            <div class="form-group" style="min-width: 200px; margin: 0;">
                <label class="form-label">Item *</label>
                <select name="inventory_item_id" class="form-select" required>
                    <option value="">— Select —</option>
                    @foreach($inventoryItems as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} ({{ number_format($item->currentStock(), 2) }} {{ $item->unit }} in stock)</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="width: 120px; margin: 0;">
                <label class="form-label">Quantity *</label>
                <input type="number" step="0.01" min="0.01" name="quantity" class="form-input" required>
            </div>
            <div class="form-group" style="width: 140px; margin: 0;">
                <label class="form-label">Cost (KES) *</label>
                <input type="number" step="0.01" min="0" name="cost" class="form-input" required>
            </div>
            <div class="form-group" style="width: 150px; margin: 0;">
                <label class="form-label">Date *</label>
                <input type="date" name="transaction_date" value="{{ now()->toDateString() }}" class="form-input" required>
            </div>
            <button type="submit" class="btn btn-secondary">Log Input</button>
        </form>
        @error('quantity') <p class="form-error">{{ $message }}</p> @enderror
    @endif
</div>

{{-- ---- Tasks & labour assignment ---- --}}
<div class="page-header">
    <h3 class="card-title" style="font-size: 17px;">Tasks &amp; Labour</h3>
</div>

{{-- Add task --}}
<div class="card" style="margin-bottom: 20px;">
    <form action="{{ route('projects.tasks.store', $project) }}" method="POST" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
        @csrf
        <div class="form-group" style="flex: 1; min-width: 220px; margin: 0;">
            <label class="form-label" for="task_name">New Task *</label>
            <input type="text" id="task_name" name="name" class="form-input" placeholder="e.g. Land preparation" required>
        </div>
        <div class="form-group" style="flex: 2; min-width: 220px; margin: 0;">
            <label class="form-label" for="task_desc">Description</label>
            <input type="text" id="task_desc" name="description" class="form-input" placeholder="Optional detail">
        </div>
        <button type="submit" class="btn btn-primary">+ Add Task</button>
    </form>
</div>

@forelse($project->tasks as $task)
    <div class="card" style="margin-bottom: 16px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 class="card-title">{{ $task->name }}
                    <span class="badge {{ $taskBadge[$task->status] ?? 'badge-neutral' }}" style="margin-left: 6px;">{{ ucwords(str_replace('_', ' ', $task->status)) }}</span>
                </h3>
                @if($task->description)<p class="page-subtitle" style="margin-top: 2px;">{{ $task->description }}</p>@endif
            </div>
            <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST" data-confirm="Delete this task and its assignments?">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
            </form>
        </div>

        @if($task->assignments->isNotEmpty())
            <div class="table-wrap" style="margin-bottom: 14px;">
                <table>
                    <thead>
                        <tr><th>Worker</th><th>Date</th><th>Hours</th><th>Rate</th><th>Cost (KES)</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($task->assignments as $a)
                        <tr>
                            <td style="font-weight: 600; color: var(--text-primary);">{{ $a->worker->name ?? '—' }}</td>
                            <td>{{ $a->assigned_date->format('M d, Y') }}</td>
                            <td>{{ number_format($a->hours, 1) }}</td>
                            <td>{{ number_format($a->rate) }}</td>
                            <td>{{ number_format($a->cost) }}</td>
                            <td>
                                <form action="{{ route('projects.assignments.destroy', [$project, $task, $a]) }}" method="POST" data-confirm="Remove this assignment?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Assign worker to this task --}}
        <form action="{{ route('projects.assignments.store', [$project, $task]) }}" method="POST" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            @csrf
            <div class="form-group" style="min-width: 180px; margin: 0;">
                <label class="form-label">Worker *</label>
                <select name="worker_id" class="form-select" required data-worker-select>
                    <option value="">— Select —</option>
                    @foreach($workers as $w)
                        <option value="{{ $w->id }}" data-rate="{{ $w->default_rate }}">{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="width: 150px; margin: 0;">
                <label class="form-label">Date *</label>
                <input type="date" name="assigned_date" value="{{ now()->toDateString() }}" class="form-input" required>
            </div>
            <div class="form-group" style="width: 110px; margin: 0;">
                <label class="form-label">Hours *</label>
                <input type="number" step="0.1" min="0" name="hours" class="form-input" required>
            </div>
            <div class="form-group" style="width: 130px; margin: 0;">
                <label class="form-label">Rate *</label>
                <input type="number" step="0.01" min="0" name="rate" class="form-input" data-rate-input required>
            </div>
            <button type="submit" class="btn btn-secondary">Assign</button>
        </form>
    </div>
@empty
    <div class="card">
        <div class="empty-state">
            <div class="icon">🧩</div>
            <h3>No tasks yet</h3>
            <p>Add a task above, then assign workers to split the work.</p>
        </div>
    </div>
@endforelse

@if($workers->isEmpty())
    <div class="alert alert-warning">No active workers yet. <a href="{{ route('workers.index') }}">Add workers</a> to assign them to tasks.</div>
@endif

<script>
    // Prefill the rate input with the selected worker's default rate.
    document.querySelectorAll('[data-worker-select]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var rate = this.options[this.selectedIndex].getAttribute('data-rate');
            var rateInput = this.closest('form').querySelector('[data-rate-input]');
            if (rateInput && !rateInput.value && rate) rateInput.value = rate;
        });
    });
</script>
@endsection
