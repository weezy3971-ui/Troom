@extends('layouts.app')
@section('title', 'M-Pesa Transactions')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">M-Pesa Transactions</h1>
        <p class="page-subtitle">Every B2C payout and C2B payment — the reconciliation log</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — M-Pesa">
            <p><strong>This is running in demo mode.</strong> No real Safaricom account is connected — B2C payouts and C2B payments here are simulated so the whole flow can be tried out. See <code>app/Services/Mpesa/DarajaGateway.php</code> for what's needed to go live.</p>
            <p><strong>B2C</strong> (paying a vendor) is triggered from an expense — open one with a vendor attached and click "Disburse via M-Pesa".</p>
            <p><strong>C2B</strong> (a customer paying) is simulated from the button below — it stands in for a customer paying at the paybill menu.</p>
        </x-help-panel>
        <a href="{{ route('mpesa.simulate-c2b.form') }}" class="btn btn-primary">Simulate C2B Payment</a>
    </div>
</div>

@if($unallocated > 0)
    <div class="alert alert-warning" style="margin-bottom: 20px;">
        {{ $unallocated }} C2B {{ \Illuminate\Support\Str::plural('payment', $unallocated) }} arrived with no matching invoice and {{ $unallocated === 1 ? 'is' : 'are' }} unallocated below — the customer likely mistyped or omitted the account reference.
    </div>
@endif

<div class="card" style="padding: 0; margin-bottom: 16px;">
    <div style="display: flex; gap: 8px; padding: 14px 22px;">
        <a href="{{ route('mpesa.index') }}" class="btn btn-sm {{ ! $direction ? 'btn-primary' : 'btn-ghost' }}">All</a>
        <a href="{{ route('mpesa.index', ['direction' => 'b2c']) }}" class="btn btn-sm {{ $direction === 'b2c' ? 'btn-primary' : 'btn-ghost' }}">B2C (Payouts)</a>
        <a href="{{ route('mpesa.index', ['direction' => 'c2b']) }}" class="btn btn-sm {{ $direction === 'c2b' ? 'btn-primary' : 'btn-ghost' }}">C2B (Received)</a>
    </div>
</div>

@if($transactions->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">📱</div>
            <h3>No M-Pesa transactions yet</h3>
            <p>Disburse a vendor payment or simulate a C2B payment to see it here.</p>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Direction</th><th>Phone</th><th>Amount</th><th>Reference</th><th>M-Pesa Code</th><th>Status</th><th>Matched To</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @foreach($transactions as $t)
                    <tr>
                        <td>
                            <span class="badge {{ $t->direction === 'b2c' ? 'badge-partial' : 'badge-active' }}">
                                {{ strtoupper($t->direction) }}
                            </span>
                        </td>
                        <td class="mono">{{ $t->phone }}</td>
                        <td class="mono" style="font-weight: 600;">{{ number_format((float) $t->amount, 2) }}</td>
                        <td class="mono">{{ $t->account_reference ?? '—' }}</td>
                        <td class="mono">{{ $t->mpesa_receipt_number ?? '—' }}</td>
                        <td>
                            <span class="badge {{ match($t->status) { 'success' => 'badge-paid', 'failed' => 'badge-voided', default => 'badge-unpaid' } }}">
                                {{ ucfirst($t->status) }}
                            </span>
                            @if($t->isUnallocated())
                                <span class="badge badge-neutral" style="margin-left:4px;">Unallocated</span>
                            @endif
                        </td>
                        <td>
                            @if($t->payable instanceof \App\Models\SalesOrder)
                                <a href="{{ route('sales-orders.show', $t->payable) }}">{{ $t->payable->invoice_number ?? 'Order #'.$t->payable->id }}</a>
                            @elseif($t->payable instanceof \App\Models\Expense)
                                <a href="{{ route('expenses.show', $t->payable) }}">{{ $t->payable->voucher_number ?? 'Expense #'.$t->payable->id }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $t->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
