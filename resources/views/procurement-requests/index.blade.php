@extends('layouts.app')
@section('title', 'Procurement')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Procurement Requests</h1>
        <p class="page-subtitle">What's needed, whether it was ordered and received — receipts post to inventory automatically</p>
    </div>
    <a href="{{ route('procurement-requests.create') }}" class="btn btn-primary">+ New Request</a>
</div>

<div style="display:flex; gap:8px; margin-bottom:16px;">
    <a href="{{ route('procurement-requests.index') }}" class="btn btn-sm {{ !$status ? 'btn-secondary' : 'btn-ghost' }}">All</a>
    @foreach(['requested' => 'Requested', 'ordered' => 'Ordered', 'received' => 'Received'] as $key => $label)
        <a href="{{ route('procurement-requests.index', ['status' => $key]) }}" class="btn btn-sm {{ $status === $key ? 'btn-secondary' : 'btn-ghost' }}">{{ $label }}</a>
    @endforeach
</div>

<x-search-bar
    :action="route('procurement-requests.index')"
    placeholder="Search by item or note…"
    :search="$search"
    :total="$requests->count()"
/>

@if($requests->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">🛒</div>
            <h3>No procurement requests</h3>
            <p>Raise a request for the inputs a stage or task needs, then track ordering and receipt.</p>
            <a href="{{ route('procurement-requests.create') }}" class="btn btn-primary">+ New Request</a>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Items</th><th>Crop Cycle</th><th>Needed By</th><th>Est. Cost</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($requests as $pr)
                    <tr>
                        <td style="font-weight:600; color: var(--text-primary);">
                            <a href="{{ route('procurement-requests.show', $pr) }}" style="color: var(--accent-hover); text-decoration: none;">#{{ $pr->id }}</a>
                        </td>
                        <td>{{ $pr->lines->count() }} item(s)</td>
                        <td>{{ $pr->cropCycle?->season_name ?? '—' }}</td>
                        <td>
                            {{ $pr->needed_by?->format('M d, Y') ?? '—' }}
                            @if($pr->isOverdue())<span class="badge badge-cancelled" style="margin-left:6px;">Overdue</span>@endif
                        </td>
                        <td>KES {{ number_format($pr->estimatedTotal()) }}</td>
                        <td>
                            <span class="badge {{ $pr->status === 'received' ? 'badge-completed' : ($pr->status === 'ordered' ? 'badge-planned' : 'badge-active') }}">{{ ucfirst($pr->status) }}</span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('procurement-requests.show', $pr) }}" class="btn btn-ghost btn-sm">View</a>
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
