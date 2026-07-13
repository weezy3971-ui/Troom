@extends('layouts.app')
@section('title', 'Dispatch #' . $dispatch->id)

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('dispatches.index') }}">Logistics</a> <span>/</span> <span>Dispatch #{{ $dispatch->id }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Dispatch #{{ $dispatch->id }}</h1>
        <p class="page-subtitle">{{ $dispatch->dispatch_date->format('M d, Y') }} — {{ $dispatch->salesOrder->customer->name ?? '' }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('dispatches.edit', $dispatch) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('dispatches.destroy', $dispatch) }}" method="POST" data-confirm="Delete this dispatch?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Status</div>
        <div class="detail-value">{{ ucwords(str_replace('_', ' ', $dispatch->status)) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Sales Order</div>
        <div class="detail-value"><a href="{{ route('sales-orders.show', $dispatch->salesOrder) }}" style="color: var(--olive); text-decoration: none;">Order #{{ $dispatch->sales_order_id }}</a></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Vehicle</div>
        <div class="detail-value">{{ $dispatch->vehicle->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Driver</div>
        <div class="detail-value">{{ $dispatch->driver->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Route</div>
        <div class="detail-value">{{ $dispatch->route ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Order Value</div>
        <div class="detail-value">KES {{ number_format($dispatch->salesOrder->orderValue(), 2) }}</div>
    </div>
</div>
@endsection
