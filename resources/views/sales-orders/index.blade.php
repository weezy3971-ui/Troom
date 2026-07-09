@extends('layouts.app')
@section('title', 'Sales Orders')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Sales Orders</h1>
        <p class="page-subtitle">Orders drawn against customer contracts</p>
    </div>
    <a href="{{ route('sales-orders.create') }}" class="btn btn-primary">+ New Order</a>
</div>

<x-search-bar
    :action="route('sales-orders.index')"
    placeholder="Search by customer…"
    :search="$search"
    :total="$orders->count()"
/>

@if($orders->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No orders match "{{ $search }}"</h3>
                <a href="{{ route('sales-orders.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">🧾</div>
                <h3>No sales orders</h3>
                <p>Create an order against a customer's contract.</p>
                <a href="{{ route('sales-orders.create') }}" class="btn btn-primary">+ New Order</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Order</th><th>Customer</th><th>Order Date</th><th>Delivery</th><th>Requested</th><th>Allocated</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('sales-orders.show', $order) }}" style="color: var(--olive); text-decoration: none;">#{{ $order->id }}</a>
                        </td>
                        <td>{{ $order->customer->name }}</td>
                        <td>{{ $order->order_date->format('M d, Y') }}</td>
                        <td>
                            {{ $order->delivery_date?->format('M d, Y') ?? '—' }}
                            @if($order->isAtRisk())<span class="badge badge-down" style="margin-left:6px;">At risk</span>@endif
                        </td>
                        <td class="mono">{{ number_format($order->requested_quantity, 0) }}</td>
                        <td class="mono">{{ number_format($order->allocatedQuantity(), 0) }}</td>
                        <td><span class="badge badge-neutral">{{ ucfirst($order->status) }}</span></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('sales-orders.show', $order) }}" class="btn btn-ghost btn-sm">View</a>
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
