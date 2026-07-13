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

        {{-- Reusable budget template --}}
        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border);">
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 4px;">Default Budget Template (optional)</h4>
            <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 14px;">These figures pre-fill a new crop cycle's seasonal budget so you don't re-enter them each season.</p>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="default_labour_budget">Labour (KES)</label>
                    <input type="number" id="default_labour_budget" name="default_labour_budget" value="{{ old('default_labour_budget') }}" class="form-input" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label" for="default_input_budget">Inputs (KES)</label>
                    <input type="number" id="default_input_budget" name="default_input_budget" value="{{ old('default_input_budget') }}" class="form-input" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label" for="default_irrigation_budget">Irrigation (KES)</label>
                    <input type="number" id="default_irrigation_budget" name="default_irrigation_budget" value="{{ old('default_irrigation_budget') }}" class="form-input" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label" for="default_overhead_budget">Overhead (KES)</label>
                    <input type="number" id="default_overhead_budget" name="default_overhead_budget" value="{{ old('default_overhead_budget') }}" class="form-input" step="0.01" min="0">
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Crop</button>
            <a href="{{ route('crops.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
