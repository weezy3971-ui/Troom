@extends('layouts.app')
@section('title', $outgrower->name)

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $outgrower->name }}</h1>
        <p class="page-subtitle">{{ $outgrower->location ?? 'No location' }} · {{ $outgrower->phone ?? 'No phone' }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('outgrowers.edit', $outgrower) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('outgrowers.destroy', $outgrower) }}" method="POST"
              data-confirm="Delete {{ $outgrower->name }}?{{ $outgrower->salesOrderLines->count() ? ' This outgrower has ' . $outgrower->salesOrderLines->count() . ' linked order line(s).' : '' }}"
              data-confirm-ok="Delete">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Status</div>
        <div class="detail-value"><span class="badge {{ $outgrower->is_active ? 'badge-active' : 'badge-neutral' }}">{{ $outgrower->is_active ? 'Active' : 'Inactive' }}</span></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Specialization</div>
        <div class="detail-value">{{ $outgrower->specialization ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Reliability</div>
        <div class="detail-value">
            @if($outgrower->reliability_rating)
                <span style="color: var(--gold); letter-spacing: 1px;">{{ str_repeat('★', $outgrower->reliability_rating) }}{{ str_repeat('☆', 5 - $outgrower->reliability_rating) }}</span>
            @else
                Not rated
            @endif
        </div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Total Supplied</div>
        <div class="detail-value">{{ number_format($outgrower->totalQuantitySupplied(), 2) }} kg</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Total Revenue</div>
        <div class="detail-value">KES {{ number_format($outgrower->totalRevenue(), 2) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Order Lines</div>
        <div class="detail-value">{{ $outgrower->salesOrderLines->count() }}</div>
    </div>
    @if($outgrower->notes)
    <div class="detail-item" style="grid-column: 1 / -1;">
        <div class="detail-label">Notes</div>
        <div class="detail-value" style="font-weight: 400; font-size: 13px;">{{ $outgrower->notes }}</div>
    </div>
    @endif
</div>

<div class="card" style="padding: 0;">
    <div class="card-header" style="padding: 18px 22px 0;"><h3 class="card-title">Sales Order Lines Supplied</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Qty (kg)</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody>
                @forelse($outgrower->salesOrderLines->sortByDesc(fn($l) => $l->salesOrder?->order_date) as $line)
                <tr>
                    <td><a href="{{ route('sales-orders.show', $line->salesOrder) }}" style="color: var(--olive); text-decoration: none;">Order #{{ $line->sales_order_id }}</a></td>
                    <td>{{ $line->salesOrder?->customer?->name ?? '—' }}</td>
                    <td>{{ $line->salesOrder?->order_date?->format('M d, Y') ?? '—' }}</td>
                    <td class="mono">{{ number_format($line->quantity, 2) }}</td>
                    <td class="mono">{{ number_format($line->unit_price, 2) }}</td>
                    <td class="mono">{{ number_format($line->lineTotal(), 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 24px;">No order lines yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
