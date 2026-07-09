@extends('layouts.app')
@section('title', 'Sales Order #' . $salesOrder->id)

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('sales-orders.index') }}">Sales Orders</a> <span>/</span> <span>#{{ $salesOrder->id }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Order #{{ $salesOrder->id }} — {{ $salesOrder->customer->name }}</h1>
        <p class="page-subtitle">Ordered {{ $salesOrder->order_date->format('M d, Y') }} · Delivery {{ $salesOrder->delivery_date?->format('M d, Y') ?? '—' }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('sales-orders.edit', $salesOrder) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('sales-orders.destroy', $salesOrder) }}" method="POST" onsubmit="return confirm('Delete this order?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

@if($salesOrder->isAtRisk())
    <div class="alert alert-warning">⚠ Order at risk — the delivery date is approaching without sufficient quality-passed lot quantity allocated.</div>
@endif

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Status</div>
        <div class="detail-value">{{ ucfirst($salesOrder->status) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Crop</div>
        <div class="detail-value">{{ $salesOrder->crop->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Requested</div>
        <div class="detail-value">{{ number_format($salesOrder->requested_quantity, 2) }} kg</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Allocated</div>
        <div class="detail-value">{{ number_format($salesOrder->allocatedQuantity(), 2) }} kg</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Order Value</div>
        <div class="detail-value">KES {{ number_format($salesOrder->orderValue(), 2) }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start;">
    {{-- Allocated lines --}}
    <div class="card" style="padding: 0;">
        <div class="card-header" style="padding: 18px 22px 0;"><h3 class="card-title">Allocated Lots</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Lot</th><th>Trace Code</th><th>Qty</th><th>Unit Price</th><th>Total</th><th></th></tr></thead>
                <tbody>
                    @forelse($salesOrder->lines as $line)
                    <tr>
                        <td>{{ $line->packhouseLot->lot_number ?? '—' }}</td>
                        <td class="mono">{{ $line->packhouseLot->traceability_code ?? '—' }}</td>
                        <td class="mono">{{ number_format($line->quantity, 2) }}</td>
                        <td class="mono">{{ number_format($line->unit_price, 2) }}</td>
                        <td class="mono">{{ number_format($line->lineTotal(), 2) }}</td>
                        <td>
                            <form action="{{ route('sales-orders.lines.destroy', [$salesOrder, $line]) }}" method="POST" onsubmit="return confirm('Remove this line?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 24px;">No lots allocated yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Allocate a lot --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Allocate Lot</h3></div>
        @if($availableLots->isEmpty())
            <p class="page-subtitle">No quality-passed lots are available to allocate.</p>
        @else
        <form action="{{ route('sales-orders.lines.store', $salesOrder) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="packhouse_lot_id">Quality-passed Lot *</label>
                <select id="packhouse_lot_id" name="packhouse_lot_id" class="form-select" required>
                    <option value="">Select lot</option>
                    @foreach($availableLots as $lot)
                        <option value="{{ $lot->id }}">{{ $lot->lot_number }} — {{ number_format($lot->quantity_packed, 0) }} kg</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity">Quantity (kg) *</label>
                <input type="number" step="0.01" id="quantity" name="quantity" class="form-input" min="0.01" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="unit_price">Unit Price (KES) *</label>
                <input type="number" step="0.01" id="unit_price" name="unit_price" class="form-input" min="0" required>
            </div>
            <button type="submit" class="btn btn-primary">Allocate</button>
        </form>
        @endif
    </div>
</div>
@endsection
