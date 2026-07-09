@extends('layouts.app')
@section('title', 'Logistics')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Logistics &amp; Dispatch</h1>
        <p class="page-subtitle">Delivery of sales orders to customers</p>
    </div>
    <a href="{{ route('dispatches.create') }}" class="btn btn-primary">+ Schedule Dispatch</a>
</div>

<x-search-bar
    :action="route('dispatches.index')"
    placeholder="Search by route or customer…"
    :search="$search"
    :total="$dispatches->count()"
/>

@if($dispatches->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No dispatches match "{{ $search }}"</h3>
                <a href="{{ route('dispatches.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">🚚</div>
                <h3>No dispatches</h3>
                <p>Schedule a dispatch against an order ready for delivery.</p>
                <a href="{{ route('dispatches.create') }}" class="btn btn-primary">+ Schedule Dispatch</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Date</th><th>Order</th><th>Customer</th><th>Vehicle</th><th>Driver</th><th>Route</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($dispatches as $dispatch)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('dispatches.show', $dispatch) }}" style="color: var(--olive); text-decoration: none;">{{ $dispatch->dispatch_date->format('M d, Y') }}</a>
                        </td>
                        <td>#{{ $dispatch->sales_order_id }}</td>
                        <td>{{ $dispatch->salesOrder->customer->name ?? '—' }}</td>
                        <td>{{ $dispatch->vehicle->name ?? '—' }}</td>
                        <td>{{ $dispatch->driver->name ?? '—' }}</td>
                        <td>{{ $dispatch->route ?? '—' }}</td>
                        <td><span class="badge badge-neutral">{{ ucwords(str_replace('_', ' ', $dispatch->status)) }}</span></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('dispatches.show', $dispatch) }}" class="btn btn-ghost btn-sm">View</a>
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
