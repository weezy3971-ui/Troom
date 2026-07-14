@extends('layouts.app')
@section('title', 'Fertigation Log')

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $fertigationLog->nutrient_type }}</h1>
        <p class="page-subtitle">{{ $fertigationLog->log_date->format('M d, Y') }} — {{ $fertigationLog->cropCycle->season_name }} ({{ $fertigationLog->cropCycle->block->name }})</p>
    </div>
    <div class="actions">
        <a href="{{ route('fertigation-logs.edit', $fertigationLog) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('fertigation-logs.destroy', $fertigationLog) }}" method="POST" data-confirm="Delete this log and its cost allocation?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Quantity</div>
        <div class="detail-value">{{ number_format($fertigationLog->quantity, 2) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Method</div>
        <div class="detail-value">{{ $fertigationLog->method ? ucfirst($fertigationLog->method) : '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Cost</div>
        <div class="detail-value">KES {{ number_format($fertigationLog->cost) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Logged By</div>
        <div class="detail-value">{{ $fertigationLog->logger?->name ?? '—' }}</div>
    </div>
</div>

<div class="alert alert-info">A cost allocation row (source: fertigation) is generated automatically from this log.</div>
@endsection
