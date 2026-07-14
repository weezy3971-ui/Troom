@extends('layouts.app')
@section('title', 'Spray Log')

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $sprayLog->chemical_used }}</h1>
        <p class="page-subtitle">{{ $sprayLog->log_date->format('M d, Y') }} — {{ $sprayLog->cropCycle->season_name }} ({{ $sprayLog->cropCycle->block->name }})</p>
    </div>
    <div class="actions">
        <a href="{{ route('spray-logs.edit', $sprayLog) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('spray-logs.destroy', $sprayLog) }}" method="POST" data-confirm="Delete this spray log?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

@if($sprayLog->isPhiActive())
    <div class="alert alert-warning">⚠ Pre-harvest interval active — harvest is blocked for this block until {{ $sprayLog->phiEndsOn()->format('M d, Y') }}.</div>
@endif

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Target Pest / Disease</div>
        <div class="detail-value">{{ $sprayLog->target_pest ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Quantity</div>
        <div class="detail-value">{{ $sprayLog->quantity ? number_format($sprayLog->quantity, 2) : '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Pre-Harvest Interval</div>
        <div class="detail-value">{{ $sprayLog->pre_harvest_interval_days }} days</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">PHI Clears</div>
        <div class="detail-value">{{ $sprayLog->phiEndsOn()->format('M d, Y') }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Applicator</div>
        <div class="detail-value">{{ $sprayLog->applicator?->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Photo</div>
        <div class="detail-value">{{ $sprayLog->photo_path ?? '—' }}</div>
    </div>
</div>
@endsection
