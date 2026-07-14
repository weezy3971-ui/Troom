@extends('layouts.app')
@section('title', $customer->name)

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $customer->name }}</h1>
        <p class="page-subtitle">{{ $customer->contact ?? 'No contact on file' }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('sales-orders.create') }}" class="btn btn-primary">+ New Order</a>
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('customers.destroy', $customer) }}" method="POST" data-confirm="Delete this customer?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Price List</div>
        <div class="detail-value">{{ $customer->price_list ?? '—' }}</div>
    </div>
    <div class="detail-item" style="grid-column: span 2;">
        <div class="detail-label">Contract Terms</div>
        <div class="detail-value" style="font-weight: 400; font-size: 13px;">{{ $customer->contract_terms ?? '—' }}</div>
    </div>
</div>

<div class="card" style="padding: 0;">
    <div class="card-header" style="padding: 18px 22px 0;"><h3 class="card-title">Sales Orders</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Order</th><th>Date</th><th>Crop</th><th>Qty</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($customer->salesOrders->sortByDesc('order_date') as $order)
                <tr>
                    <td><a href="{{ route('sales-orders.show', $order) }}" style="color: var(--olive); text-decoration: none;">Order #{{ $order->id }}</a></td>
                    <td>{{ $order->order_date->format('M d, Y') }}</td>
                    <td>{{ $order->crop->name ?? '—' }}</td>
                    <td class="mono">{{ number_format($order->requested_quantity, 0) }}</td>
                    <td><span class="badge badge-neutral">{{ ucfirst($order->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center; color: var(--text-muted); padding: 24px;">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
