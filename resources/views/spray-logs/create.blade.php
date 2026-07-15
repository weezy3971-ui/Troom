@extends('layouts.app')
@section('title', 'Log Spray')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Log Spray Event</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('spray-logs.store') }}" method="POST">
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
                <label class="form-label" for="chemical_used">Chemical Used *</label>
                <input type="text" id="chemical_used" name="chemical_used" value="{{ old('chemical_used') }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="target_pest">Target Pest / Disease</label>
                <input type="text" id="target_pest" name="target_pest" value="{{ old('target_pest') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity">Quantity</label>
                <input type="number" step="0.01" id="quantity" name="quantity" value="{{ old('quantity') }}" class="form-input" min="0">
            </div>
            <div class="form-group">
                <label class="form-label" for="pre_harvest_interval_days">Pre-Harvest Interval (days) *</label>
                <input type="number" id="pre_harvest_interval_days" name="pre_harvest_interval_days" value="{{ old('pre_harvest_interval_days', 0) }}" class="form-input" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="applicator_id">Applicator</label>
                <select id="applicator_id" name="applicator_id" class="form-select">
                    <option value="">— Select —</option>
                    @foreach($applicators as $user)
                        <option value="{{ $user->id }}" {{ old('applicator_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="photo_path">Photo reference</label>
                <input type="text" id="photo_path" name="photo_path" value="{{ old('photo_path') }}" class="form-input">
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Log Spray</button>
            <a href="{{ route('spray-logs.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
