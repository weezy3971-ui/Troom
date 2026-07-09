@extends('layouts.app')
@section('title', 'New Crop Cycle')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('crop-cycles.index') }}">Crop Cycles</a> <span>/</span> <span>New Crop Cycle</span>
</div>
<div class="page-header"><h1 class="page-title">New Crop Cycle</h1></div>

<div class="card" style="max-width: 700px;">
    <form action="{{ route('crop-cycles.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="block_id">Block *</label>
                <select id="block_id" name="block_id" class="form-select" required>
                    <option value="">Select a block</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" {{ old('block_id') == $block->id ? 'selected' : '' }}>{{ $block->name }} ({{ $block->farm->name }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="crop_id">Crop *</label>
                <select id="crop_id" name="crop_id" class="form-select" required>
                    <option value="">Select a crop</option>
                    @foreach($crops as $crop)
                        <option value="{{ $crop->id }}" {{ old('crop_id') == $crop->id ? 'selected' : '' }}>{{ $crop->name }} {{ $crop->variety ? '('.$crop->variety.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="season_name">Season Name *</label>
                <input type="text" id="season_name" name="season_name" value="{{ old('season_name') }}" class="form-input" required placeholder="e.g. Q3 2026, Long Rains 2026">
            </div>
            <div class="form-group">
                <label class="form-label" for="planting_date">Planting Date</label>
                <input type="date" id="planting_date" name="planting_date" value="{{ old('planting_date') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="expected_harvest_date">Expected Harvest Date</label>
                <input type="date" id="expected_harvest_date" name="expected_harvest_date" value="{{ old('expected_harvest_date') }}" class="form-input">
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Create Crop Cycle</button>
            <a href="{{ route('crop-cycles.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
