@extends('layouts.app')
@section('title', 'Edit Packhouse Lot')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Packhouse Lot</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('packhouse-lots.update', $packhouseLot) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="detail-item" style="margin-bottom: 20px;">
            <div class="detail-label">Traceability Code (immutable)</div>
            <div class="detail-value mono">{{ $packhouseLot->traceability_code }}</div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="lot_number">Lot Number *</label>
                <input type="text" id="lot_number" name="lot_number" value="{{ old('lot_number', $packhouseLot->lot_number) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="pack_date">Pack Date *</label>
                <input type="date" id="pack_date" name="pack_date" value="{{ old('pack_date', $packhouseLot->pack_date->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity_packed">Quantity Packed (kg) *</label>
                <input type="number" step="0.01" id="quantity_packed" name="quantity_packed" value="{{ old('quantity_packed', $packhouseLot->quantity_packed) }}" class="form-input" min="0.01" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="packaging_type">Packaging Type</label>
                <x-combobox name="packaging_type" :value="old('packaging_type', $packhouseLot->packaging_type)" :options="\App\Support\ReferenceData::packagingTypes()" placeholder="e.g. 4kg carton" />
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('packhouse-lots.show', $packhouseLot) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
