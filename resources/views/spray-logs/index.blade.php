@extends('layouts.app')
@section('title', 'Pest & Disease')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Spray Logs</h1>
        <p class="page-subtitle">Spray program and pre-harvest interval tracking</p>
    </div>
    <a href="{{ route('spray-logs.create') }}" class="btn btn-primary">+ Log Spray</a>
</div>

<x-search-bar
    :action="route('spray-logs.index')"
    placeholder="Search by chemical, pest, season, or block…"
    :search="$search"
    :total="$logs->count()"
/>

@if($logs->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No spray logs match "{{ $search }}"</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('spray-logs.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">🐛</div>
                <h3>No sprays logged</h3>
                <p>Log spray events to protect crops and enforce pre-harvest intervals.</p>
                <a href="{{ route('spray-logs.create') }}" class="btn btn-primary">+ Log Spray</a>
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
                        <th>Crop Cycle</th>
                        <th>Chemical</th>
                        <th>Target</th>
                        <th>PHI (days)</th>
                        <th>PHI Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('spray-logs.show', $log) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $log->log_date->format('M d, Y') }}</a>
                        </td>
                        <td>{{ $log->cropCycle->season_name }} — {{ $log->cropCycle->block->name }}</td>
                        <td>{{ $log->chemical_used }}</td>
                        <td>{{ $log->target_pest ?? '—' }}</td>
                        <td>{{ $log->pre_harvest_interval_days }}</td>
                        <td>
                            @if($log->isPhiActive())
                                <span class="badge badge-down">Active until {{ $log->phiEndsOn()->format('M d') }}</span>
                            @else
                                <span class="badge badge-active">Cleared</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('spray-logs.show', $log) }}" class="btn btn-ghost btn-sm">View</a>
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
