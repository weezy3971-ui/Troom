@extends('layouts.app')
@section('title', 'Labour Attendance')

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $labourAttendance->worker_name }}</h1>
        <p class="page-subtitle">{{ $labourAttendance->attendance_date->format('M d, Y') }} — {{ $labourAttendance->task }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('labour-attendances.edit', $labourAttendance) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('labour-attendances.destroy', $labourAttendance) }}" method="POST" data-confirm="Delete this attendance and its cost allocation?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Worker Type</div>
        <div class="detail-value">{{ $labourAttendance->worker_type ? ($labourAttendance->worker_type === 'permanent' ? 'In-house (permanent)' : 'Casual') : '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Phone</div>
        <div class="detail-value">{{ $labourAttendance->worker_phone ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">National ID</div>
        <div class="detail-value">{{ $labourAttendance->worker_national_id ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Block</div>
        <div class="detail-value">{{ $labourAttendance->block?->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Crop Cycle</div>
        <div class="detail-value">{{ $labourAttendance->cropCycle?->season_name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Pay Basis</div>
        <div class="detail-value">{{ $labourAttendance->isTargetBased() ? 'Target / piece-rate' : 'Hourly' }}</div>
    </div>
    @if($labourAttendance->isTargetBased())
        <div class="detail-item">
            <div class="detail-label">Target Unit</div>
            <div class="detail-value">{{ $labourAttendance->target_unit ?? '—' }}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Target Qty</div>
            <div class="detail-value">{{ $labourAttendance->target_qty !== null ? number_format($labourAttendance->target_qty, 2) : '—' }}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Qty Completed</div>
            <div class="detail-value">{{ number_format($labourAttendance->qty_completed, 2) }} {{ $labourAttendance->target_unit }}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Rate per Unit</div>
            <div class="detail-value">KES {{ number_format($labourAttendance->rate_per_unit) }}</div>
        </div>
    @else
        @if($labourAttendance->checked_in_at)
            <div class="detail-item">
                <div class="detail-label">Checked In</div>
                <div class="detail-value">{{ $labourAttendance->checked_in_at->format('M d, H:i') }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Checked Out</div>
                <div class="detail-value">{{ $labourAttendance->checked_out_at?->format('M d, H:i') ?? '—' }}</div>
            </div>
        @endif
        <div class="detail-item">
            <div class="detail-label">Hours Worked</div>
            <div class="detail-value">{{ number_format($labourAttendance->hours_worked, 1) }}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Rate</div>
            <div class="detail-value">KES {{ number_format($labourAttendance->rate) }}/hr</div>
        </div>
    @endif
    <div class="detail-item">
        <div class="detail-label">Cost</div>
        <div class="detail-value">KES {{ number_format($labourAttendance->cost) }}</div>
    </div>
</div>

<div class="alert alert-info">A cost allocation row (source: labour) is generated automatically from this entry.</div>
@endsection
