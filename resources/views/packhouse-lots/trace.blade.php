@extends('layouts.app')
@section('title', 'Traceability Lookup')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('packhouse-lots.index') }}">Packhouse</a> <span>/</span> <span>Trace Lookup</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Traceability Lookup</h1>
        <p class="page-subtitle">Scan or enter a traceability code to resolve the full provenance chain.</p>
    </div>
</div>

<div class="card" style="max-width: 560px; margin-bottom: 24px;">
    <form method="GET" action="{{ route('trace.lookup') }}" style="display: flex; gap: 12px; align-items: flex-end;">
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label class="form-label" for="code">Traceability Code</label>
            <input type="text" id="code" name="code" value="{{ $code ?? '' }}"
                   class="form-input" placeholder="e.g. TRC-ABCDE12345"
                   autofocus autocomplete="off" style="font-family: monospace;">
        </div>
        <button type="submit" class="btn btn-primary" style="flex-shrink:0;">Look Up</button>
    </form>
</div>

@if($code && !$lot)
    <div class="alert alert-error">
        ✕ No lot found with traceability code <strong class="mono">{{ $code }}</strong>.
    </div>
@endif

@if($lot)
@php
    $batch   = $lot->harvestBatch;
    $cycle   = $batch->cropCycle;
    $block   = $cycle->block ?? $batch->block;
    $farm    = $block?->farm;
@endphp

<div style="max-width: 800px;">

    {{-- Lot Info --}}
    <div class="card" style="margin-bottom: 16px;">
        <div class="card-header">
            <h3 class="card-title">📦 Packhouse Lot</h3>
            @if($lot->isQualityFailed())
                <span class="badge badge-down" style="margin-left:8px;">Quality Failed</span>
            @elseif($lot->isQualityPassed())
                <span class="badge badge-active" style="margin-left:8px;">Quality Passed</span>
            @endif
        </div>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Traceability Code</div>
                <div class="detail-value mono">{{ $lot->traceability_code }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Lot Number</div>
                <div class="detail-value"><a href="{{ route('packhouse-lots.show', $lot) }}" style="color: var(--olive); text-decoration:none;">{{ $lot->lot_number }}</a></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Pack Date</div>
                <div class="detail-value">{{ $lot->pack_date->format('M d, Y') }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Quantity Packed</div>
                <div class="detail-value">{{ number_format($lot->quantity_packed, 2) }} kg</div>
            </div>
        </div>
    </div>

    {{-- Harvest Batch --}}
    <div class="card" style="margin-bottom: 16px;">
        <div class="card-header"><h3 class="card-title">🌾 Harvest Batch</h3></div>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Harvest Date</div>
                <div class="detail-value">{{ $batch->harvest_date->format('M d, Y') }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Quantity Harvested</div>
                <div class="detail-value">{{ number_format($batch->quantity_kg, 2) }} kg</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Rejects</div>
                <div class="detail-value">{{ number_format($batch->rejects_kg, 2) }} kg</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Harvested By</div>
                <div class="detail-value">{{ $batch->harvestedBy?->name ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- Block & Farm --}}
    <div class="card" style="margin-bottom: 16px;">
        <div class="card-header"><h3 class="card-title">🗺 Block &amp; Farm</h3></div>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Block</div>
                <div class="detail-value">{{ $block?->name ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Farm</div>
                <div class="detail-value">{{ $farm?->name ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Block Size</div>
                <div class="detail-value">{{ $block?->size_acres ?? '—' }} acres</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Soil Type</div>
                <div class="detail-value">{{ $block?->soil_type ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- Crop Cycle --}}
    <div class="card" style="margin-bottom: 16px;">
        <div class="card-header"><h3 class="card-title">🌱 Crop Cycle</h3></div>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Season</div>
                <div class="detail-value">{{ $cycle->season_name }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Crop</div>
                <div class="detail-value">{{ $cycle->crop?->name ?? '—' }} ({{ $cycle->crop?->variety ?? '' }})</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Planting Date</div>
                <div class="detail-value">{{ $cycle->planting_date?->format('M d, Y') ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Status</div>
                <div class="detail-value"><span class="badge badge-{{ $cycle->status }}">{{ ucfirst($cycle->status) }}</span></div>
            </div>
        </div>
    </div>

    {{-- Spray / Fertigation History --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
        <div class="card" style="padding: 0;">
            <div class="card-header" style="padding: 16px 20px 0;">
                <h3 class="card-title">🧪 Spray History</h3>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Chemical</th><th>PHI</th></tr></thead>
                    <tbody>
                        @forelse($cycle->sprayLogs->sortByDesc('log_date') as $spray)
                        <tr>
                            <td>{{ $spray->log_date->format('M d, Y') }}</td>
                            <td>{{ $spray->chemical_used }}</td>
                            <td class="mono">{{ $spray->pre_harvest_interval_days }}d
                                @if($spray->isPhiActive())
                                    <span style="color: #f59e0b;" title="PHI still active">⚠</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align:center; color: var(--text-muted); padding: 16px;">No spray records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="padding: 0;">
            <div class="card-header" style="padding: 16px 20px 0;">
                <h3 class="card-title">💧 Fertigation History</h3>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Nutrient</th><th>Qty</th></tr></thead>
                    <tbody>
                        @forelse($cycle->fertigationLogs->sortByDesc('log_date') as $fert)
                        <tr>
                            <td>{{ $fert->log_date->format('M d, Y') }}</td>
                            <td>{{ $fert->nutrient_type }}</td>
                            <td class="mono">{{ number_format($fert->quantity, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align:center; color: var(--text-muted); padding: 16px;">No fertigation records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Quality Checks --}}
    <div class="card" style="margin-bottom: 16px; padding: 0;">
        <div class="card-header" style="padding: 16px 20px 0;">
            <h3 class="card-title">✅ Quality Checks</h3>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Result</th><th>Inspector</th></tr></thead>
                <tbody>
                    @forelse($lot->qualityChecks->sortByDesc('check_date') as $check)
                    <tr>
                        <td>{{ $check->check_date->format('M d, Y') }}</td>
                        <td><span class="badge {{ $check->result === 'pass' ? 'badge-active' : 'badge-down' }}">{{ ucfirst($check->result) }}</span></td>
                        <td>{{ $check->inspector?->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align:center; color: var(--text-muted); padding: 16px;">No quality checks recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sales Allocations --}}
    @if($lot->salesOrderLines->isNotEmpty())
    <div class="card" style="padding: 0;">
        <div class="card-header" style="padding: 16px 20px 0;">
            <h3 class="card-title">🛒 Sales Allocations</h3>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Order</th><th>Customer</th><th>Qty</th><th>Unit Price</th></tr></thead>
                <tbody>
                    @foreach($lot->salesOrderLines as $line)
                    <tr>
                        <td><a href="{{ route('sales-orders.show', $line->salesOrder) }}" style="color: var(--olive); text-decoration:none;">Order #{{ $line->sales_order_id }}</a></td>
                        <td>{{ $line->salesOrder->customer?->name ?? '—' }}</td>
                        <td class="mono">{{ number_format($line->quantity, 2) }} kg</td>
                        <td class="mono">{{ number_format($line->unit_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endif

@endsection
