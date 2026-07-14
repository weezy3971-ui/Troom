@extends('layouts.app')
@section('title', 'Edit Planting')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Planting</h1></div>

@php $available = $nurseryBatch->remainingQuantity() + $planting->quantity; @endphp

<div class="card" style="max-width: 700px;">
    <form action="{{ route('nursery-batches.plantings.update', [$nurseryBatch, $planting]) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="crop_cycle_id">Crop Cycle *</label>
                <select id="crop_cycle_id" name="crop_cycle_id" class="form-select" required>
                    @foreach($cropCycles as $cycle)
                        <option value="{{ $cycle->id }}" {{ old('crop_cycle_id', $planting->crop_cycle_id) == $cycle->id ? 'selected' : '' }}>{{ $cycle->season_name }} — {{ $cycle->block->name }} ({{ $cycle->crop->name }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity">Quantity *</label>
                <input type="number" id="quantity" name="quantity" value="{{ old('quantity', $planting->quantity) }}" class="form-input" min="1" max="{{ $available }}" required>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Up to {{ number_format($available) }} available for this batch.</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="planting_date">Planting Date *</label>
                <input type="date" id="planting_date" name="planting_date" value="{{ old('planting_date', $planting->planting_date->toDateString()) }}" class="form-input" required>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('nursery-batches.show', $nurseryBatch) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
