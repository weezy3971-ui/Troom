@extends('layouts.app')
@section('title', 'Vendors')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Vendors</h1>
        <p class="page-subtitle">Who the farm pays — suppliers, transporters, service providers</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Vendors">
            <p><strong>A vendor is a payee.</strong> Register anyone the farm pays a bill to, then pick them on the expense so the spend has a name against it, not just a category.</p>
            <p><strong>The phone number matters.</strong> It is the M-Pesa number a payout will be sent to once B2C disbursement is live. An M-Pesa payment to a wrong number cannot be recalled — check it against the vendor's own records, not a text message.</p>
            <p><strong>Retiring a vendor?</strong> Deactivate rather than delete, so the expenses already booked against them keep their payee.</p>
        </x-help-panel>
        <a href="{{ route('vendors.create') }}" class="btn btn-primary">+ New Vendor</a>
    </div>
</div>

<x-search-bar
    :action="route('vendors.index')"
    placeholder="Search by name or phone…"
    :search="$search"
    :total="$vendors->count()"
/>

@if($vendors->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No vendors match "{{ $search }}"</h3>
                <a href="{{ route('vendors.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">🧾</div>
                <h3>No vendors yet</h3>
                <p>Register the suppliers and service providers the farm pays, so expenses record who was paid.</p>
                <a href="{{ route('vendors.create') }}" class="btn btn-primary">+ New Vendor</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>Type</th><th>M-Pesa Phone</th><th>Expenses</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($vendors as $vendor)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $vendor->name }}</td>
                        <td>{{ $vendor->typeLabel() }}</td>
                        <td class="mono">
                            @if($vendor->phone)
                                {{ $vendor->phone }}
                            @else
                                <span style="color: var(--text-muted);">not set — cannot be paid by M-Pesa</span>
                            @endif
                        </td>
                        <td class="mono">{{ $vendor->expenses_count }}</td>
                        <td>
                            <span class="badge {{ $vendor->is_active ? 'badge-active' : 'badge-neutral' }}">
                                {{ $vendor->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-ghost btn-sm">Edit</a>
                                @if($vendor->is_active)
                                <form action="{{ route('vendors.destroy', $vendor) }}" method="POST"
                                      onsubmit="return confirm('Deactivate {{ $vendor->name }}? Past expenses keep them as payee.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm">Deactivate</button>
                                </form>
                                @endif
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
