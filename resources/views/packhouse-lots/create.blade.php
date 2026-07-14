@extends('layouts.app')
@section('title', 'New Packhouse Lot')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Create Packhouse Lot</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('packhouse-lots.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="harvest_batch_id">Harvest Batch *</label>
                <select id="harvest_batch_id" name="harvest_batch_id" class="form-select" required>
                    <option value="">Select harvest batch</option>
                    @foreach($harvestBatches as $batch)
                        <option value="{{ $batch->id }}" {{ old('harvest_batch_id') == $batch->id ? 'selected' : '' }}>
                            #{{ $batch->id }} — {{ $batch->cropCycle->season_name ?? '' }} ({{ $batch->harvest_date->format('M d') }}) · {{ number_format($batch->availableToPack(), 0) }} kg available
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="lot_number">Lot Number</label>
                <input type="text" id="lot_number" name="lot_number" value="{{ old('lot_number') }}" class="form-input" placeholder="Auto-generated if blank">
            </div>
            <div class="form-group">
                <label class="form-label" for="pack_date">Pack Date *</label>
                <input type="date" id="pack_date" name="pack_date" value="{{ old('pack_date', now()->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity_packed">Quantity Packed (kg) *</label>
                <input type="number" step="0.01" id="quantity_packed" name="quantity_packed" value="{{ old('quantity_packed') }}" class="form-input" min="0.01" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="packaging_type">Packaging Type</label>
                <x-combobox name="packaging_type" :value="old('packaging_type')" :options="\App\Support\ReferenceData::packagingTypes()" placeholder="e.g. 4kg carton" />
            </div>
        </div>
        <p class="page-subtitle" style="margin-bottom: 16px;">A unique traceability code is generated automatically and cannot be changed once created.</p>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Create Lot</button>
            <a href="{{ route('packhouse-lots.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
