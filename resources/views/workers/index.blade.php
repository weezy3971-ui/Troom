@extends('layouts.app')
@section('title', 'Workers')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Workers</h1>
        <p class="page-subtitle">Labour roster available for project task assignment</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Workers">
            <p><strong>Add each worker</strong> by name and phone. Set a <strong>default hourly rate</strong> — it auto-fills when recording attendance.</p>
            <p><strong>Permanent vs Casual:</strong> permanent workers appear on the payroll; casuals are paid per-session.</p>
            <p><strong>Deactivate</strong> a worker instead of deleting — their history stays intact.</p>
            <p><strong>Quick steps:</strong> Click "+ Add Worker" → enter name, phone, and rate → choose Permanent or Casual → click "Save".</p>
        </x-help-panel>
        <a href="{{ route('workers.create') }}" class="btn btn-primary">+ Add Worker</a>
    </div>
</div>

<x-search-bar
    :action="route('workers.index')"
    placeholder="Search by name or phone…"
    :search="$search"
    :total="$workers->count()"
/>

@if($workers->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">👷</div>
            <h3>No workers yet</h3>
            <p>Add workers so they can be assigned to project tasks.</p>
            <a href="{{ route('workers.create') }}" class="btn btn-primary">+ Add Worker</a>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Employee No.</th>
                        <th>Phone</th>
                        <th>Default Rate (KES/hr)</th>
                        <th>Assignments</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workers as $worker)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $worker->name }}</td>
                        <td>
                            <span class="badge {{ $worker->worker_type === 'permanent' ? 'badge-active' : 'badge-neutral' }}">
                                {{ ucfirst($worker->worker_type ?? 'casual') }}
                            </span>
                        </td>
                        <td>{{ $worker->employee_no ?? '—' }}</td>
                        <td>{{ $worker->phone ?? '—' }}</td>
                        <td>{{ number_format($worker->default_rate) }}</td>
                        <td>{{ $worker->assignments_count }}</td>
                        <td>
                            <span class="badge {{ $worker->is_active ? 'badge-active' : 'badge-completed' }}">
                                {{ $worker->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('workers.edit', $worker) }}" class="btn btn-ghost btn-sm">Edit</a>
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
