@extends('layouts.app')
@section('title', 'Irrigation')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Irrigation Logs</h1>
        <p class="page-subtitle">Schedule and log irrigation sessions per block</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Irrigation">
            <p><strong>Log each irrigation session</strong> — select the block, pump used, and duration.</p>
            <p><strong>Water volume</strong> is optional but useful for tracking consumption and costs.</p>
            <p>If a crop program has irrigation stages, overdue sessions will raise notifications.</p>
            <p><strong>Quick steps:</strong> Click "+ Log Session" → pick the block and pump → enter duration and volume → click "Save".</p>
        </x-help-panel>
        <a href="{{ route('irrigation-logs.create') }}" class="btn btn-primary">+ Log Session</a>
    </div>
</div>

<x-search-bar
    :action="route('irrigation-logs.index')"
    placeholder="Search by block, pump, or logger…"
    :search="$search"
    :total="$logs->count()"
/>

@if($logs->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No irrigation logs match "{{ $search }}"</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('irrigation-logs.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">💧</div>
                <h3>No irrigation logged</h3>
                <p>Log irrigation sessions to track water use and flag missed windows.</p>
                <a href="{{ route('irrigation-logs.create') }}" class="btn btn-primary">+ Log Session</a>
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
                        <th>Block</th>
                        <th>Pump</th>
                        <th>Hours</th>
                        <th>Water (L)</th>
                        <th>Logged By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('irrigation-logs.show', $log) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $log->log_date->format('M d, Y') }}</a>
                        </td>
                        <td>{{ $log->block->name }}</td>
                        <td>{{ $log->pump?->name ?? '—' }}</td>
                        <td>{{ number_format($log->hours, 1) }}</td>
                        <td>{{ $log->water_volume ? number_format($log->water_volume) : '—' }}</td>
                        <td>{{ $log->logger?->name ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('irrigation-logs.show', $log) }}" class="btn btn-ghost btn-sm">View</a>
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
