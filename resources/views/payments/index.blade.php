@extends('layouts.app')
@section('title', 'Payments Received')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Payments Received</h1>
        <p class="page-subtitle">Every receipt issued, and what it settled</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Payments">
            <p><strong>Payments are recorded on the order.</strong> Open a sales order, invoice it, then record what the customer paid — a receipt is issued automatically.</p>
            <p><strong>M-Pesa payments need their transaction code.</strong> Without it the payment cannot be matched against the M-Pesa statement at month end.</p>
            <p><strong>Recorded the wrong amount?</strong> Void the receipt and record a fresh one. Receipts are never edited, so a receipt already handed to a customer keeps meaning what it said.</p>
        </x-help-panel>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-muted);">Total received</div>
    <div style="font-size: 24px; font-weight: 800; color: var(--olive);">KES {{ number_format($received, 2) }}</div>
    <p class="form-hint" style="margin-top: 4px;">Excludes voided receipts.</p>
</div>

<x-search-bar
    :action="route('payments.index')"
    placeholder="Search by receipt no., M-Pesa code, or customer…"
    :search="$search"
    :total="$payments->count()"
/>

@if($payments->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No payments match "{{ $search }}"</h3>
                <a href="{{ route('payments.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">💵</div>
                <h3>No payments recorded yet</h3>
                <p>Open a sales order, issue its invoice, then record what the customer paid.</p>
                <a href="{{ route('sales-orders.index') }}" class="btn btn-primary">Go to Sales Orders</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Receipt</th><th>Date</th><th>Customer</th><th>Method</th><th>Reference</th><th class="mono">Amount</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                    <tr @if($payment->isVoided()) style="opacity: 0.6;" @endif>
                        <td class="mono" style="font-weight: 600;">
                            {{ $payment->receipt_number }}
                            @if($payment->isVoided())
                                <span class="badge badge-voided" style="margin-left: 6px;">Voided</span>
                            @endif
                        </td>
                        <td>{{ $payment->paid_at->format('d M Y') }}</td>
                        <td>{{ $payment->customer?->name ?? 'Walk-in' }}</td>
                        <td>{{ $payment->methodLabel() }}</td>
                        <td class="mono">{{ $payment->reference ?? '—' }}</td>
                        <td class="mono" style="font-weight: 600;">{{ number_format((float) $payment->amount, 2) }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-ghost btn-sm">Receipt</a>
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
