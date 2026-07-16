@extends('layouts.app')
@section('title', 'Daily Operations')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Daily Farm Operations</h1>
        <p class="page-subtitle">The core field activity log</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Daily Operations">
            <p><strong>Log field activities</strong> such as scouting, weeding, or general maintenance.</p>
            <p><strong>Compliance:</strong> Certain activities may trigger compliance checks which auditors will review.</p>
            <p>This builds a history of what happened on each block during a crop cycle.</p>
            <p><strong>Quick steps:</strong> Click "+ Log Activity" → pick the type, block, and crop cycle → add a description → click "Save".</p>
        </x-help-panel>
        <a href="{{ route('daily-activities.create') }}" class="btn btn-primary">+ Log Activity</a>
    </div>
</div>

<x-search-bar
    :action="route('daily-activities.index')"
    placeholder="Search by activity type, block, or description…"
    :search="$search"
    :total="$activities->count()"
/>

@if($activities->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No activities match "{{ $search }}"</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('daily-activities.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">📋</div>
                <h3>No activities logged</h3>
                <p>Log field activities to build the daily report and cost base.</p>
                <a href="{{ route('daily-activities.create') }}" class="btn btn-primary">+ Log Activity</a>
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
                        <th>Activity</th>
                        <th>Block</th>
                        <th>Crop Cycle</th>
                        <th>Logged By</th>
                        <th>Compliance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $activity)
                    <tr>
                        <td>{{ $activity->activity_date->format('M d, Y') }}</td>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('daily-activities.show', $activity) }}" style="color: var(--accent-hover); text-decoration: none;">{{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}</a>
                        </td>
                        <td>{{ $activity->block->name }}</td>
                        <td>{{ $activity->cropCycle?->season_name ?? '—' }}</td>
                        <td>{{ $activity->logger?->name ?? '—' }}</td>
                        <td>
                            @if($activity->requiresCompliance())
                                <span class="badge badge-active">Captured</span>
                            @else
                                <span class="badge badge-neutral">N/A</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('daily-activities.show', $activity) }}" class="btn btn-ghost btn-sm">View</a>
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
