@extends('layouts.app')
@section('title', 'Harvest Batch')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('harvest-batches.index') }}">Harvest</a> <span>/</span> <span>Batch #{{ $harvestBatch->id }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Harvest Batch #{{ $harvestBatch->id }}</h1>
        <p class="page-subtitle">{{ $harvestBatch->harvest_date->format('M d, Y') }} — {{ $harvestBatch->cropCycle->season_name }} ({{ $harvestBatch->cropCycle->crop->name ?? '' }})</p>
    </div>
    <div class="actions">
        <a href="{{ route('harvest-batches.edit', $harvestBatch) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('harvest-batches.destroy', $harvestBatch) }}" method="POST" onsubmit="return confirm('Delete this harvest batch?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

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
