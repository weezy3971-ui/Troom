@extends('layouts.app')
@section('title', 'Add Crop')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('crops.index') }}">Crops</a> <span>/</span> <span>Add Crop</span>
</div>
<div class="page-header"><h1 class="page-title">Add Crop</h1></div>

<div class="card" style="max-width: 700px;">
    <form action="{{ route('crops.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="name">Crop Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="variety">Variety</label>
                <input type="text" id="variety" name="variety" value="{{ old('variety') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="crop_type">Crop Type *</label>
                <input type="text" id="crop_type" name="crop_type" value="{{ old('crop_type') }}" class="form-input" required placeholder="e.g. Vegetable, Fruit, Herb">
            </div>
            <div class="form-group">
                <label class="form-label" for="days_to_maturity">Days to Maturity</label>
                <input type="number" id="days_to_maturity" name="days_to_maturity" value="{{ old('days_to_maturity') }}" class="form-input" min="1">
            </div>
            <div class="form-group">
                <label class="form-label" for="expected_yield_per_acre">Expected Yield per Acre (kg)</label>
                <input type="number" id="expected_yield_per_acre" name="expected_yield_per_acre" value="{{ old('expected_yield_per_acre') }}" class="form-input" step="0.01" min="0">
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Crop</button>
            <a href="{{ route('crops.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
