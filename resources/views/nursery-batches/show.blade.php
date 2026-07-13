@extends('layouts.app')
@section('title', 'Nursery Batch')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('nursery-batches.index') }}">Nursery</a> <span>/</span> <span>Batch #{{ $nurseryBatch->id }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $nurseryBatch->crop->name }} <span style="color: var(--text-muted); font-weight: 400;">({{ $nurseryBatch->crop->variety }})</span></h1>
        <p class="page-subtitle">Batch #{{ $nurseryBatch->id }} — <span class="badge badge-{{ $nurseryBatch->status }}">{{ ucfirst($nurseryBatch->status) }}</span></p>
    </div>
    <div class="actions">
        <a href="{{ route('nursery-batches.edit', $nurseryBatch) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('nursery-batches.destroy', $nurseryBatch) }}" method="POST" data-confirm="Delete this batch?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Sow Date</div>
        <div class="detail-value">{{ $nurseryBatch->sow_date->format('M d, Y') }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Expected Ready</div>
        <div class="detail-value">{{ $nurseryBatch->expected_ready_date?->format('M d, Y') ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Quantity Sown</div>
        <div class="detail-value">{{ number_format($nurseryBatch->quantity) }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Remaining</div>
        <div class="detail-value">{{ number_format($nurseryBatch->remainingQuantity()) }}</div>
    </div>
</div>

{{-- Transplant --}}
<div class="card" style="margin-bottom: 24px; max-width: 700px;">
    <div class="card-header"><h3 class="card-title">Record Transplant</h3></div>
    @if($nurseryBatch->remainingQuantity() <= 0)
        <p style="color: var(--text-muted); font-size: 13px;">This batch has been fully transplanted.</p>
    @else
        <form action="{{ route('nursery-batches.transplant', $nurseryBatch) }}" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="crop_cycle_id">Crop Cycle *</label>
                    <select id="crop_cycle_id" name="crop_cycle_id" class="form-select" required>
                        <option value="">Select crop cycle</option>
                        @foreach($cropCycles as $cycle)
                            <option value="{{ $cycle->id }}">{{ $cycle->season_name }} — {{ $cycle->block->name }} ({{ $cycle->crop->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" class="form-input" min="1" max="{{ $nurseryBatch->remainingQuantity() }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="planting_date">Planting Date *</label>
                    <input type="date" id="planting_date" name="planting_date" value="{{ now()->toDateString() }}" class="form-input" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Record Transplant</button>
        </form>
    @endif
</div>

{{-- Plantings --}}
<div class="card">
    <div class="card-header"><h3 class="card-title">Plantings</h3></div>
    @if($nurseryBatch->plantings->isEmpty())
        <div class="empty-state" style="padding: 30px;"><p>No plantings recorded from this batch yet.</p></div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Crop Cycle</th><th>Block</th><th>Quantity</th><th style="text-align: right;">Actions</th></tr></thead>
                <tbody>
                    @foreach($nurseryBatch->plantings as $planting)
                    <tr>
                        <td>{{ $planting->planting_date->format('M d, Y') }}</td>
                        <td>{{ $planting->cropCycle->season_name }}</td>
                        <td>{{ $planting->cropCycle->block->name }}</td>
                        <td>{{ number_format($planting->quantity) }}</td>
                        <td>
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <a href="{{ route('nursery-batches.plantings.edit', [$nurseryBatch, $planting]) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form action="{{ route('nursery-batches.plantings.destroy', [$nurseryBatch, $planting]) }}" method="POST" data-confirm="Delete this planting record?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
