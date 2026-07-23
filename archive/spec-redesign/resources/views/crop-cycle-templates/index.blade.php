@extends('layouts.app')
@section('title', 'Crop Cycle Templates')

@section('content')
@php $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'crop_cycles'); @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">Crop Cycle Templates</h1>
        <p class="page-subtitle">The reusable planting-to-harvest plan each cycle runs</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Templates">
            <p>A template is written <strong>once per crop</strong> and reused by every block that plants it.</p>
            <p><strong>Stages</strong> describe the growth phases — e.g. "Fruiting, day 55–75".</p>
            <p><strong>Schedule points</strong> say exactly what to apply and when — e.g. "day 60, Mancozeb, blight prevention". These are what the reminder engine reads.</p>
            <p>Editing a template changes the plan for every cycle running it. Work already logged is untouched.</p>
        </x-help-panel>
        @if($canWrite)<a href="{{ route('crop-cycle-templates.create') }}" class="btn btn-primary">+ New Template</a>@endif
    </div>
</div>

<x-crop-tabs />

<x-search-bar
    :action="route('crop-cycle-templates.index')"
    placeholder="Search by crop or variety…"
    :search="$search"
    :total="$templates->count()"
/>

@if($templates->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No templates match your search</h3>
                <p>Try a different term.</p>
            @else
                <div class="icon">🗓</div>
                <h3>No templates yet</h3>
                <p>A template holds a crop's growth stages and its spray/input schedule. Create one before starting a cycle.</p>
                @if($canWrite)<a href="{{ route('crop-cycle-templates.create') }}" class="btn btn-primary">+ New Template</a>@endif
            @endif
        </div>
    </div>
@else
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Crop</th>
                    <th>Variety</th>
                    <th class="num">Cycle length</th>
                    <th class="num">Stages</th>
                    <th class="num">Schedule points</th>
                    <th class="num">Cycles running it</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $template)
                    <tr>
                        <td><a href="{{ route('crop-cycle-templates.show', $template) }}"><strong>{{ $template->crop_name }}</strong></a></td>
                        <td>{{ $template->variety ?: '—' }}</td>
                        <td class="num">{{ $template->total_cycle_days }} days</td>
                        <td class="num">{{ $template->stages_count }}</td>
                        <td class="num">
                            @if($template->schedule_points_count === 0)
                                <span class="badge badge-warning">none yet</span>
                            @else
                                {{ $template->schedule_points_count }}
                            @endif
                        </td>
                        <td class="num">{{ $template->crop_cycles_count }}</td>
                        <td>
                            <span class="badge {{ $template->is_active ? 'badge-success' : 'badge-muted' }}">
                                {{ $template->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
