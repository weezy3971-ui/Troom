@extends('layouts.app')
@section('title', 'Edit Inventory Item')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('inventory-items.index') }}">Inventory</a> <span>/</span>
    <a href="{{ route('inventory-items.show', $inventoryItem) }}">{{ $inventoryItem->name }}</a> <span>/</span> <span>Edit</span>
</div>
<div class="page-header"><h1 class="page-title">Edit Inventory Item</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('inventory-items.update', $inventoryItem) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="name">Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $inventoryItem->name) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="category">Category *</label>
                <select id="category" name="category" class="form-select" required>
                    @foreach(['fertilizer', 'chemical', 'seed', 'spare', 'packaging', 'other'] as $cat)
                        <option value="{{ $cat }}" {{ old('category', $inventoryItem->category) == $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="unit">Unit *</label>
                <input type="text" id="unit" name="unit" value="{{ old('unit', $inventoryItem->unit) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="reorder_level">Reorder Level *</label>
                <input type="number" step="0.01" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', $inventoryItem->reorder_level) }}" class="form-input" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="farm_id">Farm</label>
                <select id="farm_id" name="farm_id" class="form-select">
                    <option value="">— All / Central —</option>
                    @foreach($farms as $farm)
                        <option value="{{ $farm->id }}" {{ old('farm_id', $inventoryItem->farm_id) == $farm->id ? 'selected' : '' }}>{{ $farm->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('inventory-items.show', $inventoryItem) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
