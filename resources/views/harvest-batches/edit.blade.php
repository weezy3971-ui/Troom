@extends('layouts.app')
@section('title', 'Edit Harvest Batch')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Harvest Batch</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('harvest-batches.update', $harvestBatch) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="crop_cycle_id">Crop Cycle *</label>
                <select id="crop_cycle_id" name="crop_cycle_id" class="form-select" required>
                    @foreach($cropCycles as $cycle)
                        <option value="{{ $cycle->id }}" {{ old('crop_cycle_id', $harvestBatch->crop_cycle_id) == $cycle->id ? 'selected' : '' }}>{{ $cycle->season_name }} — {{ $cycle->block->name ?? '' }} ({{ $cycle->crop->name ?? '' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="block_id">Block</label>
                <select id="block_id" name="block_id" class="form-select">
                    <option value="">—</option>
                    @foreach($cropCycles->pluck('block')->filter()->unique('id') as $block)
                        <option value="{{ $block->id }}" {{ old('block_id', $harvestBatch->block_id) == $block->id ? 'selected' : '' }}>{{ $block->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="harvest_date">Harvest Date *</label>
                <input type="date" id="harvest_date" name="harvest_date" value="{{ old('harvest_date', $harvestBatch->harvest_date->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity_kg">Quantity (kg) *</label>
                <input type="number" step="0.01" id="quantity_kg" name="quantity_kg" value="{{ old('quantity_kg', $harvestBatch->quantity_kg) }}" class="form-input" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="quality_grade">Quality Grade</label>
                <x-combobox name="quality_grade" :value="old('quality_grade', $harvestBatch->quality_grade)" :options="\App\Support\ReferenceData::qualityGrades()" placeholder="e.g. Grade A" />
            </div>
            <div class="form-group">
                <label class="form-label" for="rejects_kg">Rejects (kg)</label>
                <input type="number" step="0.01" id="rejects_kg" name="rejects_kg" value="{{ old('rejects_kg', $harvestBatch->rejects_kg) }}" class="form-input" min="0">
            </div>
            <div class="form-group">
                <label class="form-label" for="harvested_by">Harvested By</label>
                <select id="harvested_by" name="harvested_by" class="form-select">
                    <option value="">— Select —</option>
                    @foreach($workers as $user)
                        <option value="{{ $user->id }}" {{ old('harvested_by', $harvestBatch->harvested_by) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('harvest-batches.show', $harvestBatch) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
