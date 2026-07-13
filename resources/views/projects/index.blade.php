@extends('layouts.app')
@section('title', 'Projects')

@php
    $statusBadge = [
        'planned' => 'badge-planned', 'active' => 'badge-active',
        'completed' => 'badge-completed', 'cancelled' => 'badge-cancelled',
    ];
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Projects</h1>
        <p class="page-subtitle">Units of work — assign labour and inputs, track spend against budget</p>
    </div>
    <div class="actions">
        <a href="{{ route('workers.index') }}" class="btn btn-ghost">Workers</a>
        <a href="{{ route('projects.create') }}" class="btn btn-primary">+ New Project</a>
    </div>
</div>

<x-search-bar
    :action="route('projects.index')"
    placeholder="Search by name or code…"
    :search="$search"
    :total="$projects->count()"
/>

@if($projects->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">📋</div>
            <h3>No projects yet</h3>
            <p>Create a project, break it into tasks, then assign workers.</p>
            <a href="{{ route('projects.create') }}" class="btn btn-primary">+ New Project</a>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Farm</th>
                        <th>Tasks</th>
                        <th>Budget (KES)</th>
                        <th>Spent (KES)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($projects as $project)
                    @php $spent = $project->totalSpend(); @endphp
                    <tr>
                        <td style="font-family: var(--font-mono); font-size: 12px;">{{ $project->code }}</td>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('projects.show', $project) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $project->name }}</a>
                        </td>
                        <td>{{ $project->farm?->name ?? '—' }}</td>
                        <td>{{ $project->tasks_count }}</td>
                        <td>{{ number_format($project->budget) }}</td>
                        <td style="{{ $project->budget > 0 && $spent > $project->budget ? 'color: var(--danger); font-weight: 600;' : '' }}">{{ number_format($spent) }}</td>
                        <td><span class="badge {{ $statusBadge[$project->status] ?? 'badge-neutral' }}">{{ ucfirst($project->status) }}</span></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('projects.show', $project) }}" class="btn btn-ghost btn-sm">View</a>
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
