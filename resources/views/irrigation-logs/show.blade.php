@extends('layouts.app')
@section('title', 'Irrigation Log')

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">Irrigation — {{ $irrigationLog->log_date->format('M d, Y') }}</h1>
        <p class="page-subtitle">{{ $irrigationLog->block->name }} ({{ $irrigationLog->block->farm->name }})</p>
    </div>
    <div class="actions">
        <a href="{{ route('irrigation-logs.edit', $irrigationLog) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('irrigation-logs.destroy', $irrigationLog) }}" method="POST" data-confirm="Delete this log?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Pump Asset</div>
        <div class="detail-value">{{ $irrigationLog->pump?->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Hours</div>
        <div class="detail-value">{{ number_format($irrigationLog->hours, 1) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Window</div>
        <div class="detail-value">{{ $irrigationLog->start_time ?? '—' }} – {{ $irrigationLog->end_time ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Water Volume</div>
        <div class="detail-value">{{ $irrigationLog->water_volume ? number_format($irrigationLog->water_volume).' L' : '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Logged By</div>
        <div class="detail-value">{{ $irrigationLog->logger?->name ?? '—' }}</div>
    </div>
</div>
@endsection
