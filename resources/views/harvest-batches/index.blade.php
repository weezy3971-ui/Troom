@extends('layouts.app')
@section('title', 'Harvest')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Harvest Management</h1>
        <p class="page-subtitle">Harvest events — where field production becomes trackable output</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Harvest">
            <p><strong>Log a harvest</strong> each time produce is picked from a block — select the crop cycle and enter the weight.</p>
            <p><strong>Grade</strong> (A/B/C) classifies quality. Rejects are weighed separately so net saleable is accurate.</p>
            <p><strong>Weight confirmation</strong> means a second person verifies the scale reading.</p>
            <p>Harvests feed into packhouse lots and yield projections on the crop cycle.</p>
            <p><strong>Quick steps:</strong> Click "+ Log Harvest" → pick the crop cycle and grade → enter the weight → click "Save".</p>
        </x-help-panel>
        <a href="{{ route('harvest-batches.create') }}" class="btn btn-primary">+ Log Harvest</a>
    </div>
</div>

<x-search-bar
    :action="route('harvest-batches.index')"
    placeholder="Search by grade, season, or block…"
    :search="$search"
    :total="$batches->count()"
/>

@if($batches->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No harvest batches match "{{ $search }}"</h3>
                <a href="{{ route('harvest-batches.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">🌾</div>
                <h3>No harvests logged</h3>
                <p>Log harvest events at the end of each picking session.</p>
                <a href="{{ route('harvest-batches.create') }}" class="btn btn-primary">+ Log Harvest</a>
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
                        <th>Block</th>
                        <th>Quantity (kg)</th>
                        <th>Grade</th>
                        <th>Rejects (kg)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batches as $batch)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('harvest-batches.show', $batch) }}" style="color: var(--olive); text-decoration: none;">{{ $batch->harvest_date->format('M d, Y') }}</a>
                        </td>
                        <td>{{ $batch->cropCycle->season_name }} ({{ $batch->cropCycle->crop->name ?? '' }})</td>
                        <td>{{ $batch->block->name ?? '—' }}</td>
                        <td class="mono">{{ number_format($batch->quantity_kg, 2) }}</td>
                        <td>{{ $batch->quality_grade ?? '—' }}</td>
                        <td class="mono">{{ number_format($batch->rejects_kg, 2) }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('harvest-batches.show', $batch) }}" class="btn btn-ghost btn-sm">View</a>
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
