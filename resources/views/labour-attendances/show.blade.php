@extends('layouts.app')
@section('title', 'Labour Attendance')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('labour-attendances.index') }}">Labour</a> <span>/</span> <span>Attendance #{{ $labourAttendance->id }}</span>
</div>

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
        <div class="detail-label">Block</div>
        <div class="detail-value">{{ $labourAttendance->block?->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Crop Cycle</div>
        <div class="detail-value">{{ $labourAttendance->cropCycle?->season_name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Hours Worked</div>
        <div class="detail-value">{{ number_format($labourAttendance->hours_worked, 1) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Rate</div>
        <div class="detail-value">KES {{ number_format($labourAttendance->rate) }}/hr</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Cost</div>
        <div class="detail-value">KES {{ number_format($labourAttendance->cost) }}</div>
    </div>
</div>

<div class="alert alert-info">A cost allocation row (source: labour) is generated automatically from this entry.</div>
@endsection
