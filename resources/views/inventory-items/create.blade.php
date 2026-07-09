@extends('layouts.app')
@section('title', 'New Inventory Item')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('inventory-items.index') }}">Inventory</a> <span>/</span> <span>New Item</span>
</div>
<div class="page-header"><h1 class="page-title">Register Inventory Item</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('inventory-items.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="name">Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="category">Category *</label>
                <select id="category" name="category" class="form-select" required>
                    @foreach(['fertilizer', 'chemical', 'seed', 'spare', 'packaging', 'other'] as $cat)
                        <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="unit">Unit *</label>
                <input type="text" id="unit" name="unit" value="{{ old('unit', 'kg') }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="reorder_level">Reorder Level *</label>
                <input type="number" step="0.01" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', 0) }}" class="form-input" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="farm_id">Farm</label>
                <select id="farm_id" name="farm_id" class="form-select">
                    <option value="">— All / Central —</option>
                    @foreach($farms as $farm)
                        <option value="{{ $farm->id }}" {{ old('farm_id') == $farm->id ? 'selected' : '' }}>{{ $farm->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Register Item</button>
            <a href="{{ route('inventory-items.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
