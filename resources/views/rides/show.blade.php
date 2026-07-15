@extends('layouts.app')
@section('title', 'Ride ' . $ride->receipt_number)

@php
    $rideBadge = [
        'pending_assignment' => 'badge-planned', 'assigned' => 'badge-growing',
        'completed' => 'badge-active', 'cancelled' => 'badge-cancelled',
    ];
    $st = $ride->effectiveStatus();
@endphp

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $ride->customer_name }}
            <span class="badge {{ $rideBadge[$st] ?? 'badge-neutral' }}" style="vertical-align: middle; margin-left: 8px;">{{ ucwords(str_replace('_', ' ', $st)) }}</span>
        </h1>
        <p class="page-subtitle"><span style="font-family: var(--font-mono);">{{ $ride->receipt_number }}</span> · {{ $ride->start_time->format('M d, Y H:i') }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('rides.edit', $ride) }}" class="btn btn-secondary">Edit</a>
        @if($ride->payment_status === 'paid')
            <a href="{{ route('rides.receipt', $ride) }}" target="_blank" class="btn btn-secondary">Print Receipt</a>
        @else
            <button type="button" class="btn btn-ghost" disabled title="Mark the ride as paid to print a receipt">Print Receipt</button>
        @endif
        @if($st !== 'cancelled' && $st !== 'completed')
        <form action="{{ route('rides.cancel', $ride) }}" method="POST" data-confirm="Cancel this ride?">
            @csrf
            <button type="submit" class="btn btn-danger">Cancel Ride</button>
        </form>
        @endif
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item"><div class="detail-label">Start</div><div class="detail-value">{{ $ride->start_time->format('M d, H:i') }}</div></div>
    <div class="detail-item"><div class="detail-label">Duration</div><div class="detail-value">{{ $ride->duration_minutes }} min</div></div>
    <div class="detail-item"><div class="detail-label">Ends</div><div class="detail-value">{{ $ride->end_time->format('M d, H:i') }}</div></div>
    <div class="detail-item"><div class="detail-label">Amount</div><div class="detail-value">KES {{ number_format($ride->amount) }}</div></div>
    <div class="detail-item"><div class="detail-label">Payment</div><div class="detail-value">{{ ucfirst($ride->payment_status) }}</div></div>
    <div class="detail-item"><div class="detail-label">Phone</div><div class="detail-value">{{ $ride->customer_phone ?? '—' }}</div></div>
    <div class="detail-item"><div class="detail-label">Horse</div><div class="detail-value">{{ $ride->horse?->name ?? 'Not assigned' }}</div></div>
    <div class="detail-item"><div class="detail-label">Guide</div><div class="detail-value">{{ $ride->guide?->name ?? 'Not assigned' }}</div></div>
</div>

@if($ride->notes)
    <div class="card" style="margin-bottom: 20px;"><p style="color: var(--text-secondary);">{{ $ride->notes }}</p></div>
@endif

{{-- ---- Assignment (stable manager) ---- --}}
@if($st === 'cancelled')
    <div class="alert alert-error">This ride is cancelled.</div>
@elseif($st === 'completed')
    <div class="alert alert-success">Ride completed. {{ $ride->horse?->name }} rests until {{ $ride->end_time->copy()->addMinutes($ride->horse?->rest_minutes ?? 0)->format('M d, H:i') }}.</div>
@else
<div class="card">
    <div class="card-header"><h3 class="card-title">{{ $ride->isAssigned() ? 'Reassign Horse & Guide' : 'Assign Horse & Guide' }}</h3></div>

    @if($horses->isEmpty())
        <div class="alert alert-warning" style="margin: 0;">No horses are free for this ride window ({{ $ride->start_time->format('H:i') }}–{{ $ride->end_time->format('H:i') }}). All are on other rides or resting. <a href="{{ route('horses.index') }}">Manage horses</a>.</div>
    @else
        <form action="{{ route('rides.assign', $ride) }}" method="POST" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            @csrf
            <div class="form-group" style="min-width: 220px; margin: 0;">
                <label class="form-label">Horse *</label>
                <select name="horse_id" class="form-select" required>
                    <option value="">— Select —</option>
                    @foreach($horses as $h)
                        <option value="{{ $h->id }}" {{ $ride->horse_id == $h->id ? 'selected' : '' }}>{{ $h->name }}{{ $h->breed ? " ({$h->breed})" : '' }}</option>
                    @endforeach
                </select>
                @error('horse_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group" style="min-width: 220px; margin: 0;">
                <label class="form-label">Guide *</label>
                <select name="guide_id" class="form-select" required>
                    <option value="">— Select —</option>
                    @foreach($guides as $g)
                        <option value="{{ $g->id }}" {{ $ride->guide_id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
                @error('guide_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-primary">{{ $ride->isAssigned() ? 'Reassign' : 'Assign' }}</button>
        </form>
        <p class="page-subtitle" style="margin-top: 10px;">Only horses free across the whole ride window (including their rest period) are listed.</p>
    @endif
</div>
@endif
@endsection
