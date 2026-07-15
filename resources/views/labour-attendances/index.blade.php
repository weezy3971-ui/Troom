@extends('layouts.app')
@section('title', 'Labour')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Labour &amp; Attendance</h1>
        <p class="page-subtitle">Daily labour deployment and cost per block and task</p>
    </div>
    <a href="{{ route('labour-attendances.create') }}" class="btn btn-primary">+ Record Attendance</a>
</div>

<x-search-bar
    :action="route('labour-attendances.index')"
    placeholder="Search by worker name, task, or block…"
    :search="$search"
    :total="$attendances->count()"
/>

@if($attendances->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No attendance records match "{{ $search }}"</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('labour-attendances.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">👷</div>
                <h3>No attendance recorded</h3>
                <p>Record daily labour to track deployment and cost.</p>
                <a href="{{ route('labour-attendances.create') }}" class="btn btn-primary">+ Record Attendance</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Worker</th>
                        <th>Task</th>
                        <th>Block</th>
                        <th>Basis</th>
                        <th>Detail</th>
                        <th>Cost (KES)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $attendance)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('labour-attendances.show', $attendance) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $attendance->attendance_date->format('M d, Y') }}</a>
                        </td>
                        <td>
                            {{ $attendance->worker_name }}
                            @if($attendance->worker_type)
                                <span class="badge {{ $attendance->worker_type === 'permanent' ? 'badge-active' : 'badge-neutral' }}" style="margin-left:4px;">{{ $attendance->worker_type === 'permanent' ? 'In-house' : 'Casual' }}</span>
                            @endif
                            @if($attendance->worker_phone)<div class="page-subtitle" style="margin:0; font-weight:400;">{{ $attendance->worker_phone }}</div>@endif
                        </td>
                        <td>{{ $attendance->task }}</td>
                        <td>{{ $attendance->block?->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $attendance->isTargetBased() ? 'badge-neutral' : 'badge-active' }}">
                                {{ $attendance->isTargetBased() ? 'Target' : 'Hourly' }}
                            </span>
                        </td>
                        <td>
                            @if($attendance->isTargetBased())
                                {{ number_format($attendance->qty_completed, 2) }} {{ $attendance->target_unit }} × {{ number_format($attendance->rate_per_unit) }}
                            @else
                                {{ number_format($attendance->hours_worked, 1) }} hr × {{ number_format($attendance->rate) }}
                            @endif
                        </td>
                        <td>{{ number_format($attendance->cost) }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('labour-attendances.show', $attendance) }}" class="btn btn-ghost btn-sm">View</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
