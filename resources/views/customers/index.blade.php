@extends('layouts.app')
@section('title', 'Customers')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Customers</h1>
        <p class="page-subtitle">Customer relationships and contract terms</p>
    </div>
    <a href="{{ route('customers.create') }}" class="btn btn-primary">+ New Customer</a>
</div>

<x-search-bar
    :action="route('customers.index')"
    placeholder="Search by name or contact…"
    :search="$search"
    :total="$customers->count()"
/>

@if($customers->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No customers match "{{ $search }}"</h3>
                <a href="{{ route('customers.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">🤝</div>
                <h3>No customers yet</h3>
                <p>Register customers to draw sales orders against their contracts.</p>
                <a href="{{ route('customers.create') }}" class="btn btn-primary">+ New Customer</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>Contact</th><th>Price List</th><th>Orders</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('customers.show', $customer) }}" style="color: var(--olive); text-decoration: none;">{{ $customer->name }}</a>
                        </td>
                        <td>{{ $customer->contact ?? '—' }}</td>
                        <td>{{ $customer->price_list ?? '—' }}</td>
                        <td class="mono">{{ $customer->sales_orders_count }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('customers.show', $customer) }}" class="btn btn-ghost btn-sm">View</a>
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
