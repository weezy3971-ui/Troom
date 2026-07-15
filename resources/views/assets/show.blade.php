@extends('layouts.app')
@section('title', $asset->name)

@section('content')
@php
    $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'master_data');
    $canCheckout = \App\Support\ModuleAccess::allows(auth()->user(), 'checkouts');
    $current = $asset->currentCheckout();
@endphp
<div class="asset-nav" style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
    <button type="button" onclick="history.back()"
            style="display:inline-flex; align-items:center; gap:6px; background:#16a34a; color:#fff; border:none; padding:7px 14px; border-radius:6px; font-weight:600; font-size:.85rem; cursor:pointer;"
            onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">← Back</button>
    <a href="{{ route('assets.index') }}"
       style="display:inline-flex; align-items:center; gap:6px; background:#16a34a; color:#fff; text-decoration:none; padding:7px 14px; border-radius:6px; font-weight:600; font-size:.85rem;"
       onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">All Assets</a>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $asset->name }}</h1>
        <p class="page-subtitle">{{ $asset->farm->name }} — <span class="badge badge-{{ $asset->type }}">{{ ucfirst($asset->type) }}</span></p>
    </div>
    @if($canWrite)
    <div class="actions">
        <a href="{{ route('assets.edit', $asset) }}" class="btn btn-secondary">Edit</a>
    </div>
    @endif
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Status</div>
        <div class="detail-value"><span class="badge badge-{{ $asset->status }}">{{ ucfirst($asset->status) }}</span></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Farm</div>
        <div class="detail-value"><a href="{{ route('farms.show', $asset->farm) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $asset->farm->name }}</a></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Purchase Date</div>
        <div class="detail-value">{{ $asset->purchase_date?->format('M d, Y') ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Hours / Mileage</div>
        <div class="detail-value">{{ number_format($asset->current_hours) }}h / {{ number_format($asset->current_mileage) }}km</div>
    </div>
</div>

{{-- Custody: check-out / check-in register --}}
@if($canCheckout)
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h3 class="card-title">Custody</h3>
        <div class="actions">
            @if($current)
                <span class="badge {{ $current->isOverdue() ? 'badge-cancelled' : 'badge-active' }}" style="align-self:center;">Out with {{ $current->holder_name }}{{ $current->isOverdue() ? ' · overdue' : '' }}</span>
                <form action="{{ route('checkouts.checkin', $current) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Check In</button>
                </form>
            @else
                <span class="badge badge-completed" style="align-self:center;">Available</span>
                <a href="{{ route('checkouts.create', ['asset' => $asset->id]) }}" class="btn btn-secondary btn-sm">Check Out</a>
            @endif
        </div>
    </div>
    @if($asset->checkouts->isEmpty())
        <div class="empty-state" style="padding: 24px;"><p>No checkout history yet.</p></div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Holder</th><th>Checked Out</th><th>Due</th><th>Returned</th><th>Issued By</th></tr>
                </thead>
                <tbody>
                    @foreach($asset->checkouts as $co)
                    <tr>
                        <td style="font-weight:600; color: var(--text-primary);">{{ $co->holder_name }}</td>
                        <td>{{ $co->checked_out_at->format('M d, H:i') }}</td>
                        <td>{{ $co->expected_return_at?->format('M d, H:i') ?? '—' }}</td>
                        <td>
                            @if($co->isReturned())
                                {{ $co->returned_at->format('M d, H:i') }}
                            @elseif($co->isOverdue())
                                <span class="badge badge-cancelled">Overdue</span>
                            @else
                                <span class="badge badge-active">Still out</span>
                            @endif
                        </td>
                        <td>{{ $co->checkedOutBy?->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endif

{{-- Status History Audit Trail --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Status History (Audit Trail)</h3>
    </div>
    @if($asset->statusHistories->isEmpty())
        <div class="empty-state" style="padding: 30px;">
            <p>No status changes recorded yet.</p>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Date</th><th>From</th><th>To</th><th>Changed By</th></tr>
                </thead>
                <tbody>
                    @foreach($asset->statusHistories->sortByDesc('changed_at') as $history)
                    <tr>
                        <td>{{ $history->changed_at->format('M d, Y H:i') }}</td>
                        <td><span class="badge badge-{{ $history->old_status }}">{{ ucfirst($history->old_status) }}</span></td>
                        <td><span class="badge badge-{{ $history->new_status }}">{{ ucfirst($history->new_status) }}</span></td>
                        <td>{{ $history->changedByUser?->name ?? 'System' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
