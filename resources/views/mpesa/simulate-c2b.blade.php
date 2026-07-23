@extends('layouts.app')
@section('title', 'Simulate C2B Payment')

@section('content')
<x-crumb-nav />
<div class="page-header">
    <h1 class="page-title">Simulate a C2B Payment</h1>
</div>

<div class="alert alert-info" style="margin-bottom: 20px;">
    This stands in for a customer paying directly at the Paybill menu, with no app involved — exactly what Safaricom
    would send Trooms as a C2B confirmation. Once real M-Pesa is connected, this page can be retired; the same
    matching logic already runs from Safaricom's actual callback.
</div>

<div class="card" style="max-width: 640px;">
    <form action="{{ route('mpesa.simulate-c2b') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="phone">Payer Phone *</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="form-input" placeholder="0712 345 678" required>
                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="amount">Amount (KES) *</label>
                <input type="number" step="0.01" id="amount" name="amount" value="{{ old('amount') }}" class="form-input" min="1" required>
                @error('amount') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="account_reference">Account Reference</label>
            <input type="text" id="account_reference" name="account_reference" value="{{ old('account_reference') }}" class="form-input" placeholder="e.g. INV-000003" list="open-invoices">
            <datalist id="open-invoices">
                @foreach($openInvoices as $order)
                    <option value="{{ $order->invoice_number }}">{{ $order->customer->name }} — balance KES {{ number_format($order->balanceDue(), 2) }}</option>
                @endforeach
            </datalist>
            <p class="form-hint">
                What the payer typed at the paybill menu — usually the invoice number. Leave blank, or type
                something that matches no invoice, to see how an unmatched payment is handled.
            </p>
        </div>

        @if($openInvoices->isNotEmpty())
        <div class="form-group">
            <label class="form-label">Open invoices, for reference</label>
            <div class="table-wrap" style="border: 1px solid var(--border); border-radius: 8px;">
                <table>
                    <thead><tr><th>Invoice</th><th>Customer</th><th class="mono">Balance Due</th></tr></thead>
                    <tbody>
                        @foreach($openInvoices as $order)
                        <tr>
                            <td class="mono">{{ $order->invoice_number }}</td>
                            <td>{{ $order->customer->name }}</td>
                            <td class="mono">{{ number_format($order->balanceDue(), 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Send Simulated Payment</button>
            <a href="{{ route('mpesa.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
