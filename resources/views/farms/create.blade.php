@extends('layouts.app')
@section('title', 'Add Farm')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('farms.index') }}">Farms</a> <span>/</span> <span>Add Farm</span>
</div>

<div class="page-header">
    <h1 class="page-title">Add Farm</h1>
</div>

<div class="card" style="max-width: 700px;">
    <form action="{{ route('farms.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="name">Farm Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="location">Location *</label>
                <input type="text" id="location" name="location" value="{{ old('location') }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="size_acres">Size (Acres) *</label>
                <input type="number" id="size_acres" name="size_acres" value="{{ old('size_acres') }}" class="form-input" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="latitude">Latitude</label>
                <input type="number" id="latitude" name="latitude" value="{{ old('latitude') }}" class="form-input" step="0.0000001">
            </div>
            <div class="form-group">
                <label class="form-label" for="longitude">Longitude</label>
                <input type="number" id="longitude" name="longitude" value="{{ old('longitude') }}" class="form-input" step="0.0000001">
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Farm</button>
            <a href="{{ route('farms.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
