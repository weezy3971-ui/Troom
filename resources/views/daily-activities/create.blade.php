@extends('layouts.app')
@section('title', 'Log Activity')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('daily-activities.index') }}">Daily Operations</a> <span>/</span> <span>Log Activity</span>
</div>
<div class="page-header"><h1 class="page-title">Log Activity</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('daily-activities.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="block_id">Block *</label>
                <select id="block_id" name="block_id" class="form-select" required>
                    <option value="">Select block</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" {{ old('block_id') == $block->id ? 'selected' : '' }}>{{ $block->name }} — {{ $block->farm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="crop_cycle_id">Crop Cycle</label>
                <select id="crop_cycle_id" name="crop_cycle_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($cropCycles as $cycle)
                        <option value="{{ $cycle->id }}" {{ old('crop_cycle_id') == $cycle->id ? 'selected' : '' }}>{{ $cycle->season_name }} — {{ $cycle->block->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="activity_type">Activity Type *</label>
                <select id="activity_type" name="activity_type" class="form-select" required>
                    <option value="">Select type</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}" {{ old('activity_type') == $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="activity_date">Date *</label>
                <input type="date" id="activity_date" name="activity_date" value="{{ old('activity_date', now()->toDateString()) }}" class="form-input" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-textarea">{{ old('description') }}</textarea>
        </div>
        <p class="page-subtitle" style="margin-bottom: 12px;">Photo &amp; GPS are required for compliance activities (e.g. spraying).</p>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="photo_path">Photo reference</label>
                <input type="text" id="photo_path" name="photo_path" value="{{ old('photo_path') }}" class="form-input" placeholder="e.g. field-photo-2026-07-08.jpg">
            </div>
            <div class="form-group">
                <label class="form-label" for="gps_location">GPS location (lat,lng)</label>
                <input type="text" id="gps_location" name="gps_location" value="{{ old('gps_location') }}" class="form-input" placeholder="e.g. -0.7167,36.4333">
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Log Activity</button>
            <a href="{{ route('daily-activities.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
