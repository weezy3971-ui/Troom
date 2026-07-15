@extends('layouts.app')
@section('title', 'General Ledger')

@section('content')
<x-crumb-nav />
<div class="page-header">
    <div>
        <h1 class="page-title">General Ledger</h1>
        <p class="page-subtitle">Every posting, newest first</p>
    </div>
</div>

<form action="{{ route('finance.ledger') }}" method="GET" class="search-bar">
    <select name="account" class="filter-select" onchange="this.form.submit()">
        <option value="">All accounts</option>
        @foreach($accounts as $account)
            <option value="{{ $account->id }}" {{ request('account') == $account->id ? 'selected' : '' }}>{{ $account->code }} — {{ $account->name }}</option>
        @endforeach
    </select>
    @if(request('account'))
        <a href="{{ route('finance.ledger') }}" class="btn-clear">✕ Clear</a>
    @endif
</form>

<div class="card" style="padding: 0;">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Account</th><th>Reference</th><th>Debit</th><th>Credit</th><th>Description</th></tr></thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->entry_date->format('M d, Y') }}</td>
                    <td>{{ $entry->account->name ?? '—' }}</td>
                    <td>{{ $entry->reference_type ? ucwords(str_replace('_', ' ', $entry->reference_type)) . ' #' . $entry->reference_id : '—' }}</td>
                    <td class="mono">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '' }}</td>
                    <td class="mono">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '' }}</td>
                    <td>{{ $entry->description }}</td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 24px;">No ledger entries.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
