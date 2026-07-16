@extends('layouts.app')
@section('title', 'Finance')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Finance — Native Ledger</h1>
        <p class="page-subtitle">Auto-populated double-entry ledger tying cost and margin to real transactions</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Finance">
            <p>The ledger is <strong>auto-populated</strong> from cost allocations and sales — you don't post journal entries by hand.</p>
            <p><strong>Post New Transactions</strong> pulls any unposted allocations into the double-entry ledger.</p>
            <p><strong>Balanced</strong> should always read "Yes" — if not, check for a missing or duplicate posting.</p>
            <p>Use <strong>Full Ledger</strong> to see every entry rather than just the recent ones shown here.</p>
            <p><strong>Quick steps:</strong> Click "Post New Transactions" to push pending allocations into the ledger → click "Full Ledger" to review every entry.</p>
        </x-help-panel>
        <a href="{{ route('finance.ledger') }}" class="btn btn-secondary">Full Ledger</a>
        <form action="{{ route('finance.post') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">Post New Transactions @if($unpostedAllocations)({{ $unpostedAllocations }})@endif</button>
        </form>
    </div>
</div>

@if($exceededCycles->isNotEmpty())
    <div class="alert alert-error">
        <div>
            <strong>Budget exceeded</strong>
            @foreach($exceededCycles as $cycle)
                <div>{{ $cycle->season_name }} ({{ $cycle->block->name }}) — actual KES {{ number_format($cycle->actualCost(), 0) }} vs budget KES {{ number_format($cycle->seasonalBudget->total_budget, 0) }}</div>
            @endforeach
        </div>
    </div>
@endif

<div class="kpi-bar">
    <div class="kpi-chip">
        <span class="kpi-chip-dot olive"></span>
        <span class="kpi-chip-label">Total Debits</span>
        <span class="kpi-chip-value olive">{{ number_format($totalDebit, 0) }}</span>
    </div>
    <div class="kpi-chip">
        <span class="kpi-chip-dot terracotta"></span>
        <span class="kpi-chip-label">Total Credits</span>
        <span class="kpi-chip-value terracotta">{{ number_format($totalCredit, 0) }}</span>
    </div>
    <div class="kpi-chip">
        <span class="kpi-chip-dot {{ abs($totalDebit - $totalCredit) < 0.01 ? 'success' : 'warning' }}"></span>
        <span class="kpi-chip-label">Balanced</span>
        <span class="kpi-chip-value {{ abs($totalDebit - $totalCredit) < 0.01 ? 'success' : 'warning' }}">{{ abs($totalDebit - $totalCredit) < 0.01 ? 'Yes' : 'No' }}</span>
    </div>
</div>

<div class="cols-2" style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); gap: 20px; align-items: start;">
    {{-- Chart of accounts --}}
    <div class="card" style="padding: 0;">
        <div class="card-header" style="padding: 18px 22px 0;"><h3 class="card-title">Chart of Accounts</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Code</th><th>Account</th><th>Type</th><th>Balance</th></tr></thead>
                <tbody>
                    @forelse($accounts as $account)
                    <tr>
                        <td class="mono">{{ $account->code }}</td>
                        <td>{{ $account->name }}</td>
                        <td>{{ ucfirst($account->type) }}</td>
                        <td class="mono">{{ number_format($account->balance(), 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center; color: var(--text-muted); padding: 24px;">No accounts yet — post transactions to seed the ledger.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent ledger entries --}}
    <div class="card" style="padding: 0;">
        <div class="card-header" style="padding: 18px 22px 0;"><h3 class="card-title">Recent Ledger Entries</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Account</th><th>Debit</th><th>Credit</th><th>Description</th></tr></thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->entry_date->format('M d, Y') }}</td>
                        <td>{{ $entry->account->name ?? '—' }}</td>
                        <td class="mono">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '' }}</td>
                        <td class="mono">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '' }}</td>
                        <td>{{ $entry->description }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center; color: var(--text-muted); padding: 24px;">No entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
