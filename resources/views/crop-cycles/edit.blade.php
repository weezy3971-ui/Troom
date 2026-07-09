@extends('layouts.app')
@section('title', 'Edit Crop Cycle')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('crop-cycles.index') }}">Crop Cycles</a> <span>/</span> <a href="{{ route('crop-cycles.show', $cropCycle) }}">{{ $cropCycle->season_name }}</a> <span>/</span> <span>Edit</span>
</div>
<div class="page-header"><h1 class="page-title">Edit Crop Cycle</h1></div>

<div class="card" style="max-width: 700px;">
    <form action="{{ route('crop-cycles.update', $cropCycle) }}" method="POST">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="block_id">Block *</label>
                <select id="block_id" name="block_id" class="form-select" required>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" {{ old('block_id', $cropCycle->block_id) == $block->id ? 'selected' : '' }}>{{ $block->name }} ({{ $block->farm->name }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="crop_id">Crop *</label>
                <select id="crop_id" name="crop_id" class="form-select" required>
                    @foreach($crops as $crop)
                        <option value="{{ $crop->id }}" {{ old('crop_id', $cropCycle->crop_id) == $crop->id ? 'selected' : '' }}>{{ $crop->name }} {{ $crop->variety ? '('.$crop->variety.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="season_name">Season Name *</label>
                <input type="text" id="season_name" name="season_name" value="{{ old('season_name', $cropCycle->season_name) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="planting_date">Planting Date</label>
                <input type="date" id="planting_date" name="planting_date" value="{{ old('planting_date', $cropCycle->planting_date?->format('Y-m-d')) }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="expected_harvest_date">Expected Harvest Date</label>
                <input type="date" id="expected_harvest_date" name="expected_harvest_date" value="{{ old('expected_harvest_date', $cropCycle->expected_harvest_date?->format('Y-m-d')) }}" class="form-input">
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Update Crop Cycle</button>
            <a href="{{ route('crop-cycles.show', $cropCycle) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
