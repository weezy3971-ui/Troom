@extends('layouts.app')
@section('title', 'Edit Fertigation Log')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('fertigation-logs.index') }}">Fertigation</a> <span>/</span>
    <a href="{{ route('fertigation-logs.show', $fertigationLog) }}">Log #{{ $fertigationLog->id }}</a> <span>/</span> <span>Edit</span>
</div>
<div class="page-header"><h1 class="page-title">Edit Fertigation Log</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('fertigation-logs.update', $fertigationLog) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="crop_cycle_id">Crop Cycle *</label>
                <select id="crop_cycle_id" name="crop_cycle_id" class="form-select" required>
                    @foreach($cropCycles as $cycle)
                        <option value="{{ $cycle->id }}" {{ old('crop_cycle_id', $fertigationLog->crop_cycle_id) == $cycle->id ? 'selected' : '' }}>{{ $cycle->season_name }} — {{ $cycle->block->name }} ({{ $cycle->crop->name }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="log_date">Date *</label>
                <input type="date" id="log_date" name="log_date" value="{{ old('log_date', $fertigationLog->log_date->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="nutrient_type">Nutrient Type *</label>
                <input type="text" id="nutrient_type" name="nutrient_type" value="{{ old('nutrient_type', $fertigationLog->nutrient_type) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity">Quantity (kg/L) *</label>
                <input type="number" step="0.01" id="quantity" name="quantity" value="{{ old('quantity', $fertigationLog->quantity) }}" class="form-input" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="method">Method</label>
                <select id="method" name="method" class="form-select">
                    <option value="">— Select —</option>
                    @foreach(['drip', 'foliar', 'manual'] as $method)
                        <option value="{{ $method }}" {{ old('method', $fertigationLog->method) == $method ? 'selected' : '' }}>{{ ucfirst($method) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="cost">Cost (KES) *</label>
                <input type="number" step="0.01" id="cost" name="cost" value="{{ old('cost', $fertigationLog->cost) }}" class="form-input" min="0" required>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('fertigation-logs.show', $fertigationLog) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
