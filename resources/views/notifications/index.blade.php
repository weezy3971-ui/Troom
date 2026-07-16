@extends('layouts.app')
@section('title', 'Notifications')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-subtitle">Proactive alerts aggregated from across every module</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Notifications">
            <p>Alerts here are generated automatically when a module crosses a threshold — no configuration needed.</p>
            <p>Each alert shows which <strong>module</strong> it came from, so you know where to go to resolve it.</p>
            <p>Visit the <strong>Executive Dashboard</strong> for the full KPI picture behind these alerts.</p>
            <p><strong>Quick steps:</strong> Click an alert's module badge → it takes you to the section where you can resolve it.</p>
        </x-help-panel>
        <a href="{{ route('analytics.index') }}" class="btn btn-secondary">Executive Dashboard</a>
    </div>
</div>

@if(empty($alerts))
    <div class="card">
        <div class="empty-state">
            <div class="icon">🔔</div>
            <h3>All clear</h3>
            <p>No active alerts. Every module is within its thresholds.</p>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ count($alerts) }} active {{ Str::plural('alert', count($alerts)) }}</h3>
        </div>
        @foreach($alerts as $alert)
            <div class="alert alert-{{ $alert['severity'] === 'danger' ? 'error' : $alert['severity'] }}" style="animation: none; align-items: flex-start;">
                <div>
                    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 2px;">
                        <strong style="text-transform: uppercase; font-size: 10px; letter-spacing: 0.6px;">{{ str_replace('_', ' ', $alert['type']) }}</strong>
                        <span class="badge badge-neutral" style="font-size: 10px;">{{ $alert['module'] }}</span>
                    </div>
                    <span>{{ $alert['message'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
