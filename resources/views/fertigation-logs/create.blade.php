@extends('layouts.app')
@section('title', 'Log Fertigation')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Log Fertigation Application</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('fertigation-logs.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="crop_cycle_id">Crop Cycle *</label>
                <select id="crop_cycle_id" name="crop_cycle_id" class="form-select" required>
                    <option value="">Select crop cycle</option>
                    @foreach($cropCycles as $cycle)
                        <option value="{{ $cycle->id }}" {{ old('crop_cycle_id') == $cycle->id ? 'selected' : '' }}>{{ $cycle->season_name }} — {{ $cycle->block->name }} ({{ $cycle->crop->name }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="log_date">Date *</label>
                <input type="date" id="log_date" name="log_date" value="{{ old('log_date', now()->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="nutrient_type">Nutrient Type *</label>
                <input type="text" id="nutrient_type" name="nutrient_type" value="{{ old('nutrient_type') }}" class="form-input" placeholder="e.g. NPK 17-17-17" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity">Quantity (kg/L) *</label>
                <input type="number" step="0.01" id="quantity" name="quantity" value="{{ old('quantity') }}" class="form-input" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="method">Method</label>
                <select id="method" name="method" class="form-select">
                    <option value="">— Select —</option>
                    @foreach(['drip', 'foliar', 'manual'] as $method)
                        <option value="{{ $method }}" {{ old('method') == $method ? 'selected' : '' }}>{{ ucfirst($method) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="cost">Cost (KES) *</label>
                <input type="number" step="0.01" id="cost" name="cost" value="{{ old('cost') }}" class="form-input" min="0" required>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Log Application</button>
            <a href="{{ route('fertigation-logs.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
