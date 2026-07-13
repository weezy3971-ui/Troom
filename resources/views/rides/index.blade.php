@extends('layouts.app')
@section('title', 'Horse Rides')

@php
    $rideBadge = [
        'pending_assignment' => 'badge-planned', 'assigned' => 'badge-growing',
        'completed' => 'badge-active', 'cancelled' => 'badge-cancelled',
    ];
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Horse Rides</h1>
        <p class="page-subtitle">Bookings, receipts and horse/guide assignment</p>
    </div>
    <div class="actions">
        <a href="{{ route('horses.index') }}" class="btn btn-ghost">Horses</a>
        <a href="{{ route('guides.index') }}" class="btn btn-ghost">Guides</a>
        <a href="{{ route('rides.create') }}" class="btn btn-primary">+ New Ride</a>
    </div>
</div>

<x-search-bar
    :action="route('rides.index')"
    placeholder="Search by receipt no. or customer…"
    :search="$search"
    :total="$rides->count()"
/>

@if($rides->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">🎟️</div>
            <h3>No rides yet</h3>
            <p>Book a ride to generate a receipt and assign a horse and guide.</p>
            <a href="{{ route('rides.create') }}" class="btn btn-primary">+ New Ride</a>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Receipt</th><th>Customer</th><th>Start</th><th>Duration</th><th>Horse</th><th>Guide</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($rides as $ride)
                    @php $st = $ride->effectiveStatus(); @endphp
                    <tr>
                        <td style="font-family: var(--font-mono); font-size: 12px;">
                            <a href="{{ route('rides.show', $ride) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $ride->receipt_number }}</a>
                        </td>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $ride->customer_name }}</td>
                        <td>{{ $ride->start_time->format('M d, H:i') }}</td>
                        <td>{{ $ride->duration_minutes }} min</td>
                        <td>{{ $ride->horse?->name ?? '—' }}</td>
                        <td>{{ $ride->guide?->name ?? '—' }}</td>
                        <td>{{ number_format($ride->amount) }}</td>
                        <td><span class="badge {{ $rideBadge[$st] ?? 'badge-neutral' }}">{{ ucwords(str_replace('_', ' ', $st)) }}</span></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('rides.show', $ride) }}" class="btn btn-ghost btn-sm">View</a>
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
