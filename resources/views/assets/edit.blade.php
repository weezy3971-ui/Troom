@extends('layouts.app')
@section('title', 'Edit Asset')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('assets.index') }}">Assets</a> <span>/</span> <a href="{{ route('assets.show', $asset) }}">{{ $asset->name }}</a> <span>/</span> <span>Edit</span>
</div>
<div class="page-header"><h1 class="page-title">Edit Asset</h1></div>

<div class="card" style="max-width: 700px;">
    <form action="{{ route('assets.update', $asset) }}" method="POST">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="farm_id">Farm *</label>
                <select id="farm_id" name="farm_id" class="form-select" required>
                    @foreach($farms as $farm)
                        <option value="{{ $farm->id }}" {{ old('farm_id', $asset->farm_id) == $farm->id ? 'selected' : '' }}>{{ $farm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="name">Asset Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $asset->name) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="type">Type *</label>
                <select id="type" name="type" class="form-select" required>
                    <option value="pump" {{ old('type', $asset->type) == 'pump' ? 'selected' : '' }}>Pump</option>
                    <option value="vehicle" {{ old('type', $asset->type) == 'vehicle' ? 'selected' : '' }}>Vehicle</option>
                    <option value="equipment" {{ old('type', $asset->type) == 'equipment' ? 'selected' : '' }}>Equipment</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="purchase_date">Purchase Date</label>
                <input type="date" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status *</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="operational" {{ old('status', $asset->status) == 'operational' ? 'selected' : '' }}>Operational</option>
                    <option value="maintenance" {{ old('status', $asset->status) == 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                    <option value="down" {{ old('status', $asset->status) == 'down' ? 'selected' : '' }}>Down</option>
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Update Asset</button>
            <a href="{{ route('assets.show', $asset) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
