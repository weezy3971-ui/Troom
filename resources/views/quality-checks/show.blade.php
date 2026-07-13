@extends('layouts.app')
@section('title', 'Quality Check')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('quality-checks.index') }}">Quality Assurance</a> <span>/</span> <span>Check #{{ $qualityCheck->id }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Quality Check #{{ $qualityCheck->id }}</h1>
        <p class="page-subtitle">{{ $qualityCheck->check_date->format('M d, Y') }} — Lot {{ $qualityCheck->packhouseLot->lot_number ?? '' }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('quality-checks.edit', $qualityCheck) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('quality-checks.destroy', $qualityCheck) }}" method="POST" data-confirm="Delete this check?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

@if($qualityCheck->result === 'fail')
    <div class="alert alert-error">✕ Failed — this lot cannot be added to a sales order line until re-graded or written off.</div>
@else
    <div class="alert alert-success">✓ Passed — lot is available for sales allocation.</div>
@endif

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Result</div>
        <div class="detail-value">{{ ucfirst($qualityCheck->result) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Lot</div>
        <div class="detail-value"><a href="{{ route('packhouse-lots.show', $qualityCheck->packhouseLot) }}" style="color: var(--olive); text-decoration: none;">{{ $qualityCheck->packhouseLot->lot_number ?? '—' }}</a></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Inspector</div>
        <div class="detail-value">{{ $qualityCheck->inspector->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Photo</div>
        <div class="detail-value">{{ $qualityCheck->photo_path ?? '—' }}</div>
    </div>
</div>

@if(!empty($qualityCheck->parameters))
<div class="card">
    <div class="card-header"><h3 class="card-title">Parameters</h3></div>
    <div class="detail-grid">
        @foreach($qualityCheck->parameters as $key => $value)
        <div class="detail-item">
            <div class="detail-label">{{ $key }}</div>
            <div class="detail-value">{{ $value }}</div>
        </div>
        @endforeach
    </div>
</div>
@else
<div class="card">
    <div class="card-header"><h3 class="card-title">Parameters</h3></div>
    <p style="color: var(--text-muted); font-size: 13px; padding: 16px 0;">No parameters recorded for this quality check.</p>
</div>
@endif
@endsection
