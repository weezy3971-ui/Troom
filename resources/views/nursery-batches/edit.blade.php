@extends('layouts.app')
@section('title', 'Edit Nursery Batch')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Nursery Batch</h1></div>

<div class="card" style="max-width: 700px;">
    <form action="{{ route('nursery-batches.update', $nurseryBatch) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="crop_id">Crop *</label>
                <select id="crop_id" name="crop_id" class="form-select" required>
                    @foreach($crops as $crop)
                        <option value="{{ $crop->id }}" {{ old('crop_id', $nurseryBatch->crop_id) == $crop->id ? 'selected' : '' }}>{{ $crop->name }} ({{ $crop->variety }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity">Quantity (seedlings) *</label>
                <input type="number" id="quantity" name="quantity" value="{{ old('quantity', $nurseryBatch->quantity) }}" class="form-input" min="1" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="sow_date">Sow Date *</label>
                <input type="date" id="sow_date" name="sow_date" value="{{ old('sow_date', $nurseryBatch->sow_date->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="expected_ready_date">Expected Ready Date</label>
                <input type="date" id="expected_ready_date" name="expected_ready_date" value="{{ old('expected_ready_date', $nurseryBatch->expected_ready_date?->toDateString()) }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status *</label>
                <select id="status" name="status" class="form-select" required>
                    @foreach(['sown', 'growing', 'ready', 'transplanted'] as $status)
                        <option value="{{ $status }}" {{ old('status', $nurseryBatch->status) == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('nursery-batches.show', $nurseryBatch) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
