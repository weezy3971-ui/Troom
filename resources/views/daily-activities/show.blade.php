@extends('layouts.app')
@section('title', 'Activity')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('daily-activities.index') }}">Daily Operations</a> <span>/</span> <span>Activity #{{ $dailyActivity->id }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ ucfirst(str_replace('_', ' ', $dailyActivity->activity_type)) }}</h1>
        <p class="page-subtitle">{{ $dailyActivity->activity_date->format('M d, Y') }} — {{ $dailyActivity->block->name }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('daily-activities.edit', $dailyActivity) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('daily-activities.destroy', $dailyActivity) }}" method="POST" onsubmit="return confirm('Delete this activity?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Block</div>
        <div class="detail-value">{{ $dailyActivity->block->name }} <span style="color: var(--text-muted); font-weight: 400;">({{ $dailyActivity->block->farm->name }})</span></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Crop Cycle</div>
        <div class="detail-value">{{ $dailyActivity->cropCycle?->season_name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Logged By</div>
        <div class="detail-value">{{ $dailyActivity->logger?->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">GPS Location</div>
        <div class="detail-value">{{ $dailyActivity->gps_location ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Photo</div>
        <div class="detail-value">{{ $dailyActivity->photo_path ?? '—' }}</div>
    </div>
</div>

@if($dailyActivity->description)
<div class="card">
    <div class="card-header"><h3 class="card-title">Description</h3></div>
    <p style="font-size: 13px; color: var(--text-secondary);">{{ $dailyActivity->description }}</p>
</div>
@endif
@endsection
