@extends('layouts.app')
@section('title', 'Crop Programs')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Crop Stage Programs</h1>
        <p class="page-subtitle">Reusable per-crop protocols — stages, timing and inputs, materialised onto each cycle</p>
    </div>
    <a href="{{ route('crop-programs.create') }}" class="btn btn-primary">+ New Program</a>
</div>

<x-search-bar
    :action="route('crop-programs.index')"
    placeholder="Search by program or crop…"
    :search="$search"
    :total="$programs->count()"
/>

@if($programs->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">🗓️</div>
            <h3>No crop programs yet</h3>
            <p>Define a stage program per crop (irrigation, fertigation, sprays…) so cycles get an automatic schedule.</p>
            <a href="{{ route('crop-programs.create') }}" class="btn btn-primary">+ New Program</a>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Program</th><th>Crop</th><th>Stages</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($programs as $program)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('crop-programs.show', $program) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $program->name }}</a>
                        </td>
                        <td>{{ $program->crop?->name ?? '—' }}</td>
                        <td>{{ $program->stages_count }}</td>
                        <td>
                            <span class="badge {{ $program->is_active ? 'badge-active' : 'badge-neutral' }}">{{ $program->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('crop-programs.show', $program) }}" class="btn btn-ghost btn-sm">View</a>
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
