@extends('layouts.app')
@section('title', 'Edit Block')

@section('content')
<x-crumb-nav />

<div class="page-header">
    <h1 class="page-title">Edit Block</h1>
</div>

<div class="card" style="max-width: 700px;">
    <form action="{{ route('blocks.update', $block) }}" method="POST">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="farm_id">Farm *</label>
                <select id="farm_id" name="farm_id" class="form-select" required>
                    @foreach($farms as $farm)
                        <option value="{{ $farm->id }}" {{ old('farm_id', $block->farm_id) == $farm->id ? 'selected' : '' }}>{{ $farm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="name">Block Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $block->name) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="size_acres">Size (Acres) *</label>
                <input type="number" id="size_acres" name="size_acres" value="{{ old('size_acres', $block->size_acres) }}" class="form-input" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="soil_type">Soil Type</label>
                <x-combobox name="soil_type" :value="old('soil_type', $block->soil_type)" :options="\App\Support\ReferenceData::soilTypes()" placeholder="e.g. Clay loam, Sandy" />
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Update Block</button>
            <a href="{{ route('blocks.show', $block) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
