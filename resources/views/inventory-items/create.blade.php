@extends('layouts.app')
@section('title', 'New Inventory Item')

@section('content')
<x-crumb-nav />
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
                <x-combobox name="category" :value="old('category')" :options="\App\Support\ReferenceData::inventoryCategories()" :required="true" placeholder="e.g. fertilizer, packaging" />
            </div>
            <div class="form-group">
                <label class="form-label" for="stage">Store Stage *</label>
                <select id="stage" name="stage" class="form-select" required>
                    @foreach(\App\Models\InventoryItem::STAGES as $value => $label)
                        <option value="{{ $value }}" {{ old('stage', 'general') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="unit">Unit *</label>
                <x-combobox name="unit" :value="old('unit', 'kg')" :options="\App\Support\ReferenceData::units()" :required="true" placeholder="e.g. kg, litre, unit" />
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
