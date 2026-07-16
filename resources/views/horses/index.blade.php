@extends('layouts.app')
@section('title', 'Horses')

@php
    $statusBadge = ['available' => 'badge-active', 'on_ride' => 'badge-growing', 'resting' => 'badge-planned'];
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Horses</h1>
        <p class="page-subtitle">Stable roster — availability reflects current rides and rest periods</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Horses">
            <p><strong>Manage the stable:</strong> register horses and configure their required rest times between rides.</p>
            <p><strong>Availability</strong> is automatically calculated based on ongoing rides and required rest periods.</p>
            <p><strong>Quick steps:</strong> Click "+ Add Horse" → enter name, breed, and rest minutes → click "Save".</p>
        </x-help-panel>
        <a href="{{ route('guides.index') }}" class="btn btn-ghost">Guides</a>
        <a href="{{ route('rides.index') }}" class="btn btn-ghost">Rides</a>
        <a href="{{ route('horses.create') }}" class="btn btn-primary">+ Add Horse</a>
    </div>
</div>

<x-search-bar
    :action="route('horses.index')"
    placeholder="Search by name or breed…"
    :search="$search"
    :total="$horses->count()"
/>

@if($horses->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">🐎</div>
            <h3>No horses yet</h3>
            <p>Add horses to the stable so they can be assigned to rides.</p>
            <a href="{{ route('horses.create') }}" class="btn btn-primary">+ Add Horse</a>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>Breed</th><th>Rest (min)</th><th>Current Status</th><th>Free At</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($horses as $horse)
                    @php $status = $horse->currentStatus(); $busy = $horse->busyUntil(); @endphp
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $horse->name }}</td>
                        <td>{{ $horse->breed ?? '—' }}</td>
                        <td>{{ $horse->rest_minutes }}</td>
                        <td>
                            @if(!$horse->is_active)
                                <span class="badge badge-completed">Inactive</span>
                            @else
                                <span class="badge {{ $statusBadge[$status] ?? 'badge-neutral' }}">{{ ucwords(str_replace('_', ' ', $status)) }}</span>
                            @endif
                        </td>
                        <td>{{ $busy ? $busy->format('M d, H:i') : '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('horses.edit', $horse) }}" class="btn btn-ghost btn-sm">Edit</a>
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
