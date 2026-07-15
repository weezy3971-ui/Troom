@extends('layouts.app')
@section('title', $packhouseLot->lot_number)

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $packhouseLot->lot_number }}</h1>
        <p class="page-subtitle">Packed {{ $packhouseLot->pack_date->format('M d, Y') }} — {{ $packhouseLot->harvestBatch->cropCycle->season_name ?? '' }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('packhouse-lots.edit', $packhouseLot) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('packhouse-lots.destroy', $packhouseLot) }}" method="POST" data-confirm="Delete this lot?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

@if($packhouseLot->isQualityFailed())
    <div class="alert alert-error">✕ This lot failed quality inspection and cannot be allocated to a sales order until re-graded or written off.</div>
@elseif($packhouseLot->isQualityPassed())
    <div class="alert alert-success">✓ Quality passed — available for sales allocation.</div>
@endif

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Traceability Code</div>
        <div class="detail-value mono">{{ $packhouseLot->traceability_code }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Quantity Packed</div>
        <div class="detail-value">{{ number_format($packhouseLot->quantity_packed, 2) }} kg</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Packaging</div>
        <div class="detail-value">{{ $packhouseLot->packaging_type ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Source Harvest</div>
        <div class="detail-value"><a href="{{ route('harvest-batches.show', $packhouseLot->harvestBatch) }}" style="color: var(--olive); text-decoration: none;">Batch #{{ $packhouseLot->harvest_batch_id }}</a></div>
    </div>
</div>

<div class="cols-2" style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px;">
    <div class="card" style="padding: 0;">
        <div class="card-header" style="padding: 18px 22px 0;">
            <h3 class="card-title">Quality Checks</h3>
            <a href="{{ route('quality-checks.create') }}" class="btn btn-ghost btn-sm">+ Check</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Result</th><th>Inspector</th></tr></thead>
                <tbody>
                    @forelse($packhouseLot->qualityChecks->sortByDesc('check_date') as $check)
                    <tr>
                        <td>{{ $check->check_date->format('M d, Y') }}</td>
                        <td><span class="badge {{ $check->result === 'pass' ? 'badge-active' : 'badge-down' }}">{{ ucfirst($check->result) }}</span></td>
                        <td>{{ $check->inspector->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align:center; color: var(--text-muted); padding: 24px;">No checks yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="padding: 0;">
        <div class="card-header" style="padding: 18px 22px 0;">
            <h3 class="card-title">Sales Allocations</h3>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Order</th><th>Quantity</th><th>Unit Price</th></tr></thead>
                <tbody>
                    @forelse($packhouseLot->salesOrderLines as $line)
                    <tr>
                        <td><a href="{{ route('sales-orders.show', $line->salesOrder) }}" style="color: var(--olive); text-decoration: none;">Order #{{ $line->sales_order_id }}</a></td>
                        <td class="mono">{{ number_format($line->quantity, 2) }} kg</td>
                        <td class="mono">{{ number_format($line->unit_price, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align:center; color: var(--text-muted); padding: 24px;">Not yet allocated.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
