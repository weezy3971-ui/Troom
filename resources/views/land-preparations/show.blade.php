@extends('layouts.app')
@section('title', 'Land Preparation — ' . $prep->block->name)

@section('content')
@php
    $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'daily_ops');
    $percent = $prep->percentComplete();
@endphp

<style>
    .lp-bar { height: 6px; border-radius: 999px; background: var(--bg-secondary); overflow: hidden; margin-top: 8px; }
    .lp-bar span { display: block; height: 100%; background: var(--olive); }
    .lp-task { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); }
    .lp-task:last-child { border-bottom: 0; }
    .lp-seq {
        flex: 0 0 24px; height: 24px; border-radius: 50%; display: grid; place-items: center;
        font-size: 11.5px; font-weight: 600; background: var(--bg-secondary); color: var(--text-muted);
    }
    .lp-task.is-done .lp-seq { background: var(--olive); color: #fff; }
    .lp-task.is-done .lp-name { text-decoration: line-through; color: var(--text-muted); }
    .lp-task.is-skipped .lp-name { color: var(--text-muted); }
    .lp-name { font-weight: 600; font-size: 13.5px; }
    .lp-desc { font-size: 12.5px; color: var(--text-secondary); line-height: 1.45; margin-top: 2px; }
    .lp-acts { margin-left: auto; display: flex; gap: 6px; align-items: flex-start; }
</style>

<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">Land Preparation</h1>
        <p class="page-subtitle">
            <a href="{{ route('blocks.show', $prep->block) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $prep->block->name }}</a>
            — {{ $prep->block->farm->name }}
            — started {{ $prep->started_on?->format('M d, Y') ?? 'not yet' }}
        </p>
    </div>
    <div class="actions">
        <span class="badge badge-{{ $prep->statusColor() }}">{{ $prep->statusLabel() }}</span>
        <a href="{{ route('blocks.show', $prep->block) }}" class="btn btn-secondary">← Block</a>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Progress</div>
        <div class="detail-value">{{ $prep->doneCount() }} of {{ $prep->tasks->count() }} steps done</div>
        <div class="lp-bar"><span style="width: {{ $percent }}%;"></span></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Still outstanding</div>
        <div class="detail-value">{{ $prep->outstandingCount() }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Cost recorded</div>
        <div class="detail-value">KES {{ number_format($prep->totalCost(), 2) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Attributed to</div>
        <div class="detail-value">
            @if($prep->cropCycle)
                <a href="{{ route('crop-cycles.show', $prep->cropCycle) }}" style="color: var(--accent-hover); text-decoration: none;">
                    {{ $prep->cropCycle->crop->name }} — {{ $prep->cropCycle->season_name }}
                </a>
            @else
                <span style="color: var(--text-muted);">No crop cycle yet</span>
            @endif
        </div>
    </div>
</div>

@if(! $prep->cropCycle && $prep->totalCost() > 0)
<div class="alert alert-warning">
    <div>
        <strong>THIS SPEND BELONGS TO NOTHING YET</strong> — KES {{ number_format($prep->totalCost(), 2) }} is
        recorded against this preparation but no crop cycle is linked to it, so it won't fall into any stage of a
        planting. Link the cycle below once the block is planted.
    </div>
</div>
@endif

<div class="cols-2" style="display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; align-items: start;">
    {{-- The checklist --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Preparation steps</h3></div>
        @foreach($prep->tasks as $task)
            <div class="lp-task {{ $task->isDone() ? 'is-done' : '' }} {{ $task->isSkipped() ? 'is-skipped' : '' }}">
                <div class="lp-seq">{{ $task->isDone() ? '✓' : $task->sequence }}</div>
                <div style="min-width: 0;">
                    <div class="lp-name">{{ $task->name }}</div>
                    <div class="lp-desc">{{ $task->description }}</div>
                    @if($task->isDone() && $task->done_on)
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Done {{ $task->done_on->format('M d, Y') }}</div>
                    @elseif($task->isSkipped())
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Marked not needed</div>
                    @endif
                </div>
                @if($canWrite)
                <div class="lp-acts">
                    @if(! $task->isDone())
                        <form action="{{ route('land-preparations.tasks.update', $task) }}" method="POST">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="done">
                            <button type="submit" class="btn btn-success btn-sm">Done</button>
                        </form>
                    @endif
                    @if($task->status === 'pending')
                        <form action="{{ route('land-preparations.tasks.update', $task) }}" method="POST">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="skipped">
                            <button type="submit" class="btn btn-ghost btn-sm">Not needed</button>
                        </form>
                    @else
                        <form action="{{ route('land-preparations.tasks.update', $task) }}" method="POST">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="pending">
                            <button type="submit" class="btn btn-ghost btn-sm">Undo</button>
                        </form>
                    @endif
                </div>
                @endif
            </div>
        @endforeach
    </div>

    <div>
        {{-- Status, dates and the link to the planting --}}
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header"><h3 class="card-title">Round details</h3></div>
            @if($canWrite)
                <form action="{{ route('land-preparations.update', $prep) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label class="form-label" for="status">Status *</label>
                        <select id="status" name="status" class="form-select" required>
                            @foreach(\App\Models\LandPreparation::STATUSES as $value => $label)
                                <option value="{{ $value }}" {{ $prep->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="started_on">Started</label>
                        <input type="date" id="started_on" name="started_on" value="{{ $prep->started_on?->toDateString() }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="completed_on">Ready to plant</label>
                        <input type="date" id="completed_on" name="completed_on" value="{{ $prep->completed_on?->toDateString() }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="crop_cycle_id">Planting this prepared for</label>
                        <select id="crop_cycle_id" name="crop_cycle_id" class="form-select">
                            <option value="">— not linked yet —</option>
                            @foreach($cycles as $cycle)
                                <option value="{{ $cycle->id }}" {{ $prep->crop_cycle_id === $cycle->id ? 'selected' : '' }}>
                                    {{ $cycle->crop->name }} — {{ $cycle->season_name }}
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 5px;">
                            Links this preparation, and its costs, to the planting that followed it.
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3" class="form-input">{{ $prep->notes }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Save</button>
                </form>
            @else
                <div class="detail-item" style="margin-bottom: 12px;">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">{{ $prep->statusLabel() }}</div>
                </div>
                @if($prep->notes)
                    <div class="detail-item">
                        <div class="detail-label">Notes</div>
                        <div class="detail-value">{{ $prep->notes }}</div>
                    </div>
                @endif
            @endif
        </div>

        {{-- The one way a block gets planted with no preparation on record --}}
        @if($canWrite && ! $prep->isSatisfied())
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header"><h3 class="card-title">Preparation not required?</h3></div>
            <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 14px;">
                If this block was prepared outside the system, or genuinely needs no preparation, record why.
                The cycle can then be activated, and the reason stays on the record.
            </p>
            <form action="{{ route('land-preparations.waive', $prep) }}" method="POST"
                  data-confirm="Record that {{ $prep->block->name }} needs no preparation? The reason is kept on the audit trail."
                  data-confirm-ok="Record as Not Required">
                @csrf @method('PUT')
                <div class="form-group">
                    <textarea name="notes" rows="2" class="form-input" required
                              placeholder="e.g. Block was ploughed and limed by the contractor last month.">{{ old('notes') }}</textarea>
                    @error('notes')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-ghost" style="width: 100%;">Mark Not Required</button>
            </form>
        </div>
        @endif

        {{-- Costs recorded against this round --}}
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header"><h3 class="card-title">Costs ({{ $prep->expenses->count() }})</h3></div>
            @forelse($prep->expenses as $expense)
                <div style="display:flex; justify-content:space-between; gap:10px; font-size:12.5px; padding:6px 0; border-bottom:1px solid var(--border);">
                    <a href="{{ route('expenses.show', $expense) }}" style="color: var(--accent-hover); text-decoration:none;">
                        {{ ucwords(str_replace('_', ' ', $expense->category)) }}
                    </a>
                    <span class="mono">{{ number_format($expense->amount, 2) }}</span>
                </div>
            @empty
                <p style="font-size:12.5px; color: var(--text-secondary); margin:0;">
                    No costs recorded yet. When logging an expense, choose this preparation under
                    "Land preparation" so the spend attaches to it.
                </p>
            @endforelse
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Guidance</h3></div>
            <ul style="margin: 0; padding-left: 16px; font-size: 12px; color: var(--text-secondary);">
                @foreach($sources as $source)
                    <li style="margin-bottom: 5px;"><a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
