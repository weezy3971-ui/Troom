@extends('layouts.app')
@section('title', 'Asset Checkouts')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Asset Checkouts</h1>
        <p class="page-subtitle">Who has which tool or asset, when it went out, and whether it's back</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Asset Checkouts">
            <p><strong>Check out tools</strong> to workers so there's a record of who took them and when they are due back.</p>
            <p><strong>Check in:</strong> Record when the asset is returned to the store.</p>
            <p><strong>Overdue:</strong> Badges will flag items that have not been returned by their expected time.</p>
            <p><strong>Quick steps:</strong> Click "+ Check Out Asset" → pick the asset and holder → set an expected return → click "Save", then click "Check In" when it's back.</p>
        </x-help-panel>
        <a href="{{ route('checkouts.create') }}" class="btn btn-primary">+ Check Out Asset</a>
    </div>
</div>

<div style="display:flex; gap:8px; margin-bottom:16px;">
    <a href="{{ route('checkouts.index', ['filter' => 'outstanding']) }}" class="btn btn-sm {{ $filter === 'outstanding' ? 'btn-secondary' : 'btn-ghost' }}">Outstanding</a>
    <a href="{{ route('checkouts.index', ['filter' => 'all']) }}" class="btn btn-sm {{ $filter === 'all' ? 'btn-secondary' : 'btn-ghost' }}">All History</a>
</div>

<x-search-bar
    :action="route('checkouts.index')"
    placeholder="Search by holder or asset…"
    :search="$search"
    :total="$checkouts->count()"
/>

@if($checkouts->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">🧰</div>
            <h3>{{ $filter === 'outstanding' ? 'Nothing is checked out' : 'No checkout records' }}</h3>
            <p>Check an asset out to record who took it and when.</p>
            <a href="{{ route('checkouts.create') }}" class="btn btn-primary">+ Check Out Asset</a>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Asset</th><th>Holder</th><th>Checked Out</th><th>Due</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($checkouts as $co)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $co->asset?->name ?? '—' }}</td>
                        <td>{{ $co->holder_name }}</td>
                        <td>{{ $co->checked_out_at->format('M d, H:i') }}</td>
                        <td>{{ $co->expected_return_at?->format('M d, H:i') ?? '—' }}</td>
                        <td>
                            @if($co->isReturned())
                                <span class="badge badge-completed">Returned {{ $co->returned_at->format('M d') }}</span>
                            @elseif($co->isOverdue())
                                <span class="badge badge-cancelled">Overdue</span>
                            @else
                                <span class="badge badge-active">Out</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                @unless($co->isReturned())
                                <form action="{{ route('checkouts.checkin', $co) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm">Check In</button>
                                </form>
                                @endunless
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
