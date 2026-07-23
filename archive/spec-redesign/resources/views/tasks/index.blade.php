@extends('layouts.app')
@section('title', 'Tasks')

@section('content')
@php
    $groups = [
        ['label' => 'Overdue', 'rows' => $overdue, 'class' => 'badge-danger'],
        ['label' => 'Due today', 'rows' => $today, 'class' => 'badge-warning'],
        ['label' => 'Upcoming', 'rows' => $upcoming, 'class' => 'badge-muted'],
    ];
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Tasks</h1>
        <p class="page-subtitle">Raised automatically from each active cycle's schedule</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Tasks">
            <p>Tasks appear on their due day, worked out from the cycle's planting date plus the template's schedule point.</p>
            <p><strong>Log the activity</strong> on the crop cycle rather than ticking the task — that captures the product, dosage, and cost, and closes the task for you.</p>
            <p>Anything still open 24 hours past its date escalates to the farm manager.</p>
        </x-help-panel>
        <a href="{{ route('tasks.index', ['scope' => $scope === 'mine' ? 'all' : 'mine']) }}" class="btn btn-secondary">
            {{ $scope === 'mine' ? 'Show all tasks' : 'Show only mine' }}
        </a>
    </div>
</div>

@if($tasks->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">✓</div>
            <h3>Nothing outstanding</h3>
            <p>{{ $scope === 'mine' ? 'You have no open tasks.' : 'No open tasks on any active cycle.' }}</p>
        </div>
    </div>
@else
    @foreach($groups as $group)
        @continue($group['rows']->isEmpty())
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">{{ $group['label'] }} <span class="badge {{ $group['class'] }}">{{ $group['rows']->count() }}</span></h2>
            </div>
            <table class="table">
                <thead>
                    <tr><th>Due</th><th>Task</th><th>Block</th><th>Cycle</th><th>Assigned to</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($group['rows'] as $task)
                        <tr>
                            <td>
                                {{ $task->due_date?->format('d M Y') ?? '—' }}
                                @if($task->isOverdue())
                                    <br><span class="badge badge-danger">{{ $task->daysOverdue() }}d late</span>
                                @endif
                            </td>
                            <td>{{ $task->description }}</td>
                            <td>{{ $task->cropCycle?->block?->name ?? '—' }}</td>
                            <td>
                                @if($task->cropCycle)
                                    <a href="{{ route('crop-cycles.show', $task->cropCycle) }}">{{ $task->cropCycle->season_name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                            <td class="num">
                                @if($task->cropCycle)
                                    <a href="{{ route('crop-cycles.show', $task->cropCycle) }}#log-activity" class="btn btn-sm btn-primary">Log work</a>
                                @endif
                                <form action="{{ route('tasks.complete', $task) }}" method="POST"
                                      data-confirm="Mark done without logging an activity? The cost won't be recorded.">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-ghost">Mark done</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endif
@endsection
