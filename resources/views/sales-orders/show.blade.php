@extends('layouts.app')
@section('title', 'Sales Order #' . $salesOrder->id)

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">Order #{{ $salesOrder->id }} — {{ $salesOrder->customer->name }}</h1>
        <p class="page-subtitle">Ordered {{ $salesOrder->order_date->format('M d, Y') }} · Delivery {{ $salesOrder->delivery_date?->format('M d, Y') ?? '—' }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('sales-orders.edit', $salesOrder) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('sales-orders.destroy', $salesOrder) }}" method="POST" data-confirm="Delete this order?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

@if($salesOrder->isAtRisk())
    <div class="alert alert-warning">⚠ Order at risk — the delivery date is approaching without sufficient quality-passed lot quantity allocated.</div>
@endif

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Status</div>
        <div class="detail-value">{{ ucfirst($salesOrder->status) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Crop</div>
        <div class="detail-value">{{ $salesOrder->crop->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Requested</div>
        <div class="detail-value">{{ number_format($salesOrder->requested_quantity, 2) }} kg</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Allocated</div>
        <div class="detail-value">{{ number_format($salesOrder->allocatedQuantity(), 2) }} kg</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Order Value</div>
        <div class="detail-value">KES {{ number_format($salesOrder->orderValue(), 2) }}</div>
    </div>
    @if($salesOrder->isInvoiced())
    <div class="detail-item">
        <div class="detail-label">Invoice</div>
        <div class="detail-value mono">{{ $salesOrder->invoice_number }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Invoiced / Paid</div>
        <div class="detail-value">
            KES {{ number_format((float) $salesOrder->total_amount, 2) }}
            <span style="color: var(--text-muted);">/</span>
            KES {{ number_format((float) $salesOrder->amount_paid, 2) }}
        </div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Payment</div>
        <div class="detail-value">
            <span class="badge badge-{{ $salesOrder->payment_status }}">{{ ucfirst($salesOrder->payment_status) }}</span>
        </div>
    </div>
    @endif
</div>

<div class="cols-2" style="display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 20px; align-items: start;">
    {{-- Allocated lines --}}
    <div class="card" style="padding: 0;">
        <div class="card-header" style="padding: 18px 22px 0;"><h3 class="card-title">Allocated Sources</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Source</th><th>Trace Code</th><th>Qty</th><th>Unit Price</th><th>Total</th><th></th></tr></thead>
                <tbody>
                    @forelse($salesOrder->lines as $line)
                    <tr>
                        <td>
                            {{ $line->sourceLabel() }}
                            @if($line->isFromOutgrower())<span class="badge badge-neutral" style="margin-left:6px;">Outgrower</span>@endif
                        </td>
                        <td class="mono">{{ $line->isFromOutgrower() ? '—' : ($line->packhouseLot->traceability_code ?? '—') }}</td>
                        <td class="mono">{{ number_format($line->quantity, 2) }}</td>
                        <td class="mono">{{ number_format($line->unit_price, 2) }}</td>
                        <td class="mono">{{ number_format($line->lineTotal(), 2) }}</td>
                        <td>
                            <form action="{{ route('sales-orders.lines.destroy', [$salesOrder, $line]) }}" method="POST" data-confirm="Remove this line?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 24px;">No lots allocated yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Allocate a source (in-house lot or outgrower top-up) --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Add Source</h3></div>
        <form action="{{ route('sales-orders.lines.store', $salesOrder) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="source">Source *</label>
                <select id="source" name="source" class="form-select" data-source required>
                    <option value="lot" {{ old('source', 'lot') === 'lot' ? 'selected' : '' }}>In-house lot</option>
                    <option value="outgrower" {{ old('source') === 'outgrower' ? 'selected' : '' }}>Outgrower (top up)</option>
                </select>
            </div>

            <div class="form-group" data-source-lot>
                <label class="form-label" for="packhouse_lot_id">Quality-passed Lot *</label>
                @if($availableLots->isEmpty())
                    <p class="page-subtitle" style="margin:0;">No quality-passed lots available — use an outgrower to fulfil.</p>
                @else
                <select id="packhouse_lot_id" name="packhouse_lot_id" class="form-select">
                    <option value="">Select lot</option>
                    @foreach($availableLots as $lot)
                        <option value="{{ $lot->id }}">{{ $lot->lot_number }} — {{ number_format($lot->quantity_packed, 0) }} kg</option>
                    @endforeach
                </select>
                @endif
                @error('packhouse_lot_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-group" data-source-outgrower hidden>
                <label class="form-label" for="outgrower_id">Outgrower *</label>
                @if($outgrowers->isEmpty())
                    <p class="page-subtitle" style="margin:0;">No outgrowers yet. <a href="{{ route('outgrowers.create') }}">Add one</a>.</p>
                @else
                <select id="outgrower_id" name="outgrower_id" class="form-select">
                    <option value="">Select outgrower</option>
                    @foreach($outgrowers as $og)
                        <option value="{{ $og->id }}">{{ $og->name }}@if($og->location) — {{ $og->location }}@endif</option>
                    @endforeach
                </select>
                @endif
                @error('outgrower_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="quantity">Quantity (kg) *</label>
                <input type="number" step="0.01" id="quantity" name="quantity" class="form-input" min="0.01" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="unit_price">Unit Price (KES) *</label>
                <input type="number" step="0.01" id="unit_price" name="unit_price" class="form-input" min="0" required>
            </div>
            <button type="submit" class="btn btn-primary">Add</button>
        </form>
    </div>
</div>

<script>
    (function () {
        var src = document.querySelector('[data-source]');
        var lot = document.querySelector('[data-source-lot]');
        var og = document.querySelector('[data-source-outgrower]');
        if (!src) return;
        function toggle() {
            var isOg = src.value === 'outgrower';
            lot.hidden = isOg;
            og.hidden = !isOg;
        }
        src.addEventListener('change', toggle);
        toggle();
    })();
</script>

{{-- Theme 8: rejects / returns tracking --}}
<div class="card" style="margin-top: 20px;">
    <div class="card-header"><h3 class="card-title">Delivery Outcome — Rejects &amp; Returns</h3></div>

    @php $rejectRate = $salesOrder->rejectRate(); @endphp
    <div class="detail-grid" style="padding: 12px 22px;">
        <div class="detail-item">
            <div class="detail-label">Delivered</div>
            <div class="detail-value">{{ number_format($salesOrder->delivered_quantity, 2) }} kg</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Rejected</div>
            <div class="detail-value">{{ number_format($salesOrder->rejected_quantity, 2) }} kg</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Returned</div>
            <div class="detail-value">{{ number_format($salesOrder->returned_quantity, 2) }} kg</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Reject Rate</div>
            <div class="detail-value">{{ $rejectRate === null ? '—' : number_format($rejectRate * 100, 1) . '%' }}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Amount Repaid</div>
            <div class="detail-value">KES {{ number_format($salesOrder->amount_repaid) }}</div>
        </div>
    </div>

    <form action="{{ route('sales-orders.delivery', $salesOrder) }}" method="POST" style="padding: 0 22px 22px;">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="delivered_quantity">Delivered (kg) *</label>
                <input type="number" step="0.01" id="delivered_quantity" name="delivered_quantity" value="{{ old('delivered_quantity', $salesOrder->delivered_quantity) }}" class="form-input" min="0" required>
                @error('delivered_quantity') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="rejected_quantity">Rejected (kg)</label>
                <input type="number" step="0.01" id="rejected_quantity" name="rejected_quantity" value="{{ old('rejected_quantity', $salesOrder->rejected_quantity) }}" class="form-input" min="0">
            </div>
            <div class="form-group">
                <label class="form-label" for="returned_quantity">Returned (kg)</label>
                <input type="number" step="0.01" id="returned_quantity" name="returned_quantity" value="{{ old('returned_quantity', $salesOrder->returned_quantity) }}" class="form-input" min="0">
            </div>
            <div class="form-group">
                <label class="form-label" for="amount_repaid">Amount Repaid (KES)</label>
                <input type="number" step="0.01" id="amount_repaid" name="amount_repaid" value="{{ old('amount_repaid', $salesOrder->amount_repaid) }}" class="form-input" min="0">
            </div>
        </div>
        <div style="margin-top: 12px;">
            <button type="submit" class="btn btn-primary">Save Delivery Outcome</button>
        </div>
    </form>
</div>

{{-- Payments & receipting --}}
<div class="card" style="margin-top: 20px; padding: 0;">
    <div class="card-header"><h3 class="card-title">Payments &amp; Receipts</h3></div>

    @if(! $salesOrder->isInvoiced())
        {{-- Invoicing is what fixes the amount owed. Until then there is
             nothing for a payment to settle, so the form is not offered. --}}
        <div style="padding: 4px 22px 22px;">
            <p style="color: var(--text-secondary); font-size: 13.5px; margin-bottom: 14px;">
                This order has not been invoiced. Issuing an invoice freezes the current order value of
                <strong>KES {{ number_format($salesOrder->orderValue(), 2) }}</strong> as the amount owed and
                gives the customer a number to quote when paying.
            </p>
            @error('invoice') <p class="form-error" style="margin-bottom: 12px;">{{ $message }}</p> @enderror
            <form action="{{ route('sales-orders.invoice', $salesOrder) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary" @disabled($salesOrder->orderValue() <= 0)>
                    Issue Invoice
                </button>
                @if($salesOrder->orderValue() <= 0)
                    <p class="form-hint" style="margin-top: 8px;">Allocate at least one priced line first.</p>
                @endif
            </form>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th>Reference</th><th class="mono">Amount</th><th></th></tr></thead>
                <tbody>
                    @forelse($salesOrder->payments as $payment)
                    <tr @if($payment->isVoided()) style="opacity: 0.6;" @endif>
                        <td class="mono" style="font-weight: 600;">
                            {{ $payment->receipt_number }}
                            @if($payment->isVoided())<span class="badge badge-voided" style="margin-left:6px;">Voided</span>@endif
                        </td>
                        <td>{{ $payment->paid_at->format('M d, Y') }}</td>
                        <td>{{ $payment->methodLabel() }}</td>
                        <td class="mono">{{ $payment->reference ?? '—' }}</td>
                        <td class="mono">{{ number_format((float) $payment->amount, 2) }}</td>
                        <td><a href="{{ route('payments.receipt', $payment) }}" class="btn btn-ghost btn-sm">Receipt</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 24px;">No payments recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($salesOrder->balanceDue() > 0)
            <form action="{{ route('payments.store', $salesOrder) }}" method="POST" style="padding: 18px 22px 22px; border-top: 1px solid var(--border);">
                @csrf
                <p style="font-size: 13.5px; color: var(--text-secondary); margin-bottom: 14px;">
                    Outstanding: <strong>KES {{ number_format($salesOrder->balanceDue(), 2) }}</strong>
                </p>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="amount">Amount Received (KES) *</label>
                        <input type="number" step="0.01" id="amount" name="amount"
                               value="{{ old('amount', number_format($salesOrder->balanceDue(), 2, '.', '')) }}"
                               class="form-input" min="0.01" required>
                        @error('amount') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="method">Method *</label>
                        <select id="method" name="method" class="form-input" required>
                            @foreach(\App\Models\Payment::METHOD_LABELS as $method => $label)
                                <option value="{{ $method }}" @selected(old('method') === $method)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="paid_at">Date Received *</label>
                        <input type="date" id="paid_at" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="reference">Reference / M-Pesa Code</label>
                        <input type="text" id="reference" name="reference" value="{{ old('reference') }}" class="form-input" placeholder="e.g. SGH4KLM9XZ">
                        <p class="form-hint">Required for M-Pesa — it is how the payment is reconciled later.</p>
                        @error('reference') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="payer_phone">Payer Phone</label>
                        <input type="text" id="payer_phone" name="payer_phone"
                               value="{{ old('payer_phone', $salesOrder->customer->phone) }}" class="form-input" placeholder="0712 345 678">
                    </div>
                </div>
                <div style="margin-top: 12px;">
                    <button type="submit" class="btn btn-primary">Record Payment &amp; Issue Receipt</button>
                </div>
            </form>
        @else
            <div style="padding: 4px 22px 22px;">
                <span class="badge badge-paid">Paid in full</span>
            </div>
        @endif
    @endif
</div>
@endsection
