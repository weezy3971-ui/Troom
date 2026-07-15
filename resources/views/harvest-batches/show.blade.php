@extends('layouts.app')
@section('title', 'Harvest Batch')

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">Harvest Batch #{{ $harvestBatch->id }}</h1>
        <p class="page-subtitle">{{ $harvestBatch->harvest_date->format('M d, Y') }} — {{ $harvestBatch->cropCycle->season_name }} ({{ $harvestBatch->cropCycle->crop->name ?? '' }})</p>
    </div>
    <div class="actions">
        @unless($harvestBatch->isConfirmed())
        <form action="{{ route('harvest-batches.confirm', $harvestBatch) }}" method="POST" data-confirm="Confirm the weighed quantity for this batch?">
            @csrf
            <button type="submit" class="btn btn-success">✓ Confirm Weight</button>
        </form>
        @endunless
        <a href="{{ route('harvest-batches.edit', $harvestBatch) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('harvest-batches.destroy', $harvestBatch) }}" method="POST" data-confirm="Delete this harvest batch?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<x-help-panel title="Guide — harvest, weighing & by-products">
    <p><strong>Confirming weight:</strong> a second person verifies the weighed quantity — the batch shows who confirmed and when. Confirm once the weight is checked.</p>
    <p><strong>Net saleable</strong> = quantity harvested − rejects, and <strong>available to pack</strong> subtracts what's already gone to packhouse lots.</p>
    <p><strong>By-products</strong> (e.g. offcut leaves) are recorded separately below so they don't inflate the main saleable weight.</p>
    <p>Harvest is blocked while an active pre-harvest interval (spray withholding) applies to the crop cycle.</p>
</x-help-panel>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Quantity Harvested</div>
        <div class="detail-value">{{ number_format($harvestBatch->quantity_kg, 2) }} kg</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Rejects</div>
        <div class="detail-value">{{ number_format($harvestBatch->rejects_kg, 2) }} kg</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Net Saleable</div>
        <div class="detail-value">{{ number_format($harvestBatch->netQuantity(), 2) }} kg</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Available to Pack</div>
        <div class="detail-value">{{ number_format($harvestBatch->availableToPack(), 2) }} kg</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Quality Grade</div>
        <div class="detail-value">{{ $harvestBatch->quality_grade ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Harvested By</div>
        <div class="detail-value">{{ $harvestBatch->harvestedBy->name ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Weight Confirmed</div>
        <div class="detail-value">
            @if($harvestBatch->isConfirmed())
                <span class="badge badge-active">✓ {{ $harvestBatch->confirmedBy->name ?? 'Confirmed' }}</span>
                <span style="font-size: 12px; color: var(--text-muted);">{{ $harvestBatch->confirmed_at->format('M d, Y H:i') }}</span>
            @else
                <span class="badge badge-down">Unconfirmed</span>
            @endif
        </div>
    </div>
</div>

{{-- Yield vs. Expected --}}
@php
    $crop          = $harvestBatch->cropCycle->crop;
    $block         = $harvestBatch->cropCycle->block ?? $harvestBatch->block;
    $expectedYield = ($crop?->expected_yield_per_acre ?? 0) * ($block?->size_acres ?? 0);
    $actualYield   = $harvestBatch->cropCycle->harvestBatches->sum('quantity_kg');
    $yieldPct      = $expectedYield > 0 ? min(round(($actualYield / $expectedYield) * 100, 1), 999) : null;
    $yieldShort    = $expectedYield > 0 && $actualYield < $expectedYield;
@endphp
@if($expectedYield > 0)
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title">Yield vs. Budget</h3>
        @if($yieldShort)
            <span class="badge badge-down" style="margin-left: 10px;">Below Target</span>
        @else
            <span class="badge badge-active" style="margin-left: 10px;">On Target</span>
        @endif
    </div>
    <div style="padding: 16px 0 8px;">
        <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">
            <span>Harvested (cycle total): <strong>{{ number_format($actualYield, 2) }} kg</strong></span>
            <span>Expected: <strong>{{ number_format($expectedYield, 2) }} kg</strong></span>
        </div>
        <div style="background: var(--border); border-radius: 6px; height: 10px; overflow: hidden;">
            <div style="height: 100%; width: {{ min($yieldPct, 100) }}%; background: {{ $yieldShort ? '#f59e0b' : 'var(--accent, #6366f1)' }}; border-radius: 6px; transition: width 0.4s ease;"></div>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); margin-top: 6px;">
            <span>{{ $yieldPct }}% of target achieved</span>
            <span>{{ $crop?->name }} · {{ $block?->size_acres }} acres expected @ {{ number_format($crop?->expected_yield_per_acre ?? 0, 1) }} kg/acre</span>
        </div>
    </div>
</div>
@endif

{{-- By-products --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title">By-products</h3>
        @if($harvestBatch->byProducts->isNotEmpty())
            <span class="badge badge-planned" style="margin-left: 10px;">{{ number_format($harvestBatch->byProducts->sum('quantity_kg'), 2) }} kg total</span>
        @endif
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>By-product</th><th>Quantity</th><th>Notes</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($harvestBatch->byProducts as $bp)
                <tr>
                    <td>{{ $bp->name }}</td>
                    <td class="mono">{{ number_format($bp->quantity_kg, 2) }} kg</td>
                    <td style="color: var(--text-muted);">{{ $bp->notes ?? '—' }}</td>
                    <td style="text-align:right;">
                        <form action="{{ route('harvest-batches.by-products.destroy', [$harvestBatch, $bp]) }}" method="POST" data-confirm="Remove this by-product?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; color: var(--text-muted); padding: 20px;">No by-products recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding: 16px 22px; border-top: 1px solid var(--border);">
        <form action="{{ route('harvest-batches.by-products.store', $harvestBatch) }}" method="POST">
            @csrf
            <div class="form-grid" style="margin-bottom: 12px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label" for="bp_name">By-product *</label>
                    <input type="text" id="bp_name" name="name" value="{{ old('name') }}" class="form-input" placeholder="e.g. Offcut leaves (mofefe)" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" for="bp_qty">Quantity (kg) *</label>
                    <input type="number" step="0.01" id="bp_qty" name="quantity_kg" value="{{ old('quantity_kg') }}" class="form-input" min="0" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" for="bp_notes">Notes</label>
                    <input type="text" id="bp_notes" name="notes" value="{{ old('notes') }}" class="form-input" placeholder="optional">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">+ Add By-product</button>
        </form>
    </div>
</div>

<div class="card" style="padding: 0;">
    <div class="card-header" style="padding: 18px 22px 0;">
        <h3 class="card-title">Packhouse Lots</h3>
        <a href="{{ route('packhouse-lots.create') }}" class="btn btn-ghost btn-sm">+ New Lot</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Lot #</th><th>Pack Date</th><th>Quantity</th><th>Trace Code</th></tr>
            </thead>
            <tbody>
                @forelse($harvestBatch->packhouseLots as $lot)
                <tr>
                    <td><a href="{{ route('packhouse-lots.show', $lot) }}" style="color: var(--olive); text-decoration: none;">{{ $lot->lot_number }}</a></td>
                    <td>{{ $lot->pack_date->format('M d, Y') }}</td>
                    <td class="mono">{{ number_format($lot->quantity_packed, 2) }} kg</td>
                    <td class="mono">{{ $lot->traceability_code }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; color: var(--text-muted); padding: 24px;">Not yet packed.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

