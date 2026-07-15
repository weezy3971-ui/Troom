@extends('layouts.app')
@section('title', 'New Nursery Batch')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">New Nursery Batch</h1></div>

<div class="card" style="max-width: 700px;">
    <form action="{{ route('nursery-batches.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="crop_id">Crop *</label>
                <select id="crop_id" name="crop_id" class="form-select" required>
                    <option value="">Select crop</option>
                    @foreach($crops as $crop)
                        <option value="{{ $crop->id }}" {{ old('crop_id') == $crop->id ? 'selected' : '' }}>{{ $crop->name }} ({{ $crop->variety }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity">Quantity (seedlings) *</label>
                <input type="number" id="quantity" name="quantity" value="{{ old('quantity') }}" class="form-input" min="1" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="sow_date">Sow Date *</label>
                <input type="date" id="sow_date" name="sow_date" value="{{ old('sow_date', now()->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="expected_ready_date">Expected Ready Date</label>
                <input type="date" id="expected_ready_date" name="expected_ready_date" value="{{ old('expected_ready_date') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status *</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="sown" {{ old('status', 'sown') == 'sown' ? 'selected' : '' }}>Sown</option>
                    <option value="growing" {{ old('status') == 'growing' ? 'selected' : '' }}>Growing</option>
                    <option value="ready" {{ old('status') == 'ready' ? 'selected' : '' }}>Ready</option>
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Create Batch</button>
            <a href="{{ route('nursery-batches.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
