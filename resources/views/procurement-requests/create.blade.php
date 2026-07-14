@extends('layouts.app')
@section('title', 'New Procurement Request')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">New Procurement Request</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('procurement-requests.store') }}" method="POST">
        @csrf
        <div class="form-grid">
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
                <label class="form-label" for="farm_id">Farm</label>
                <select id="farm_id" name="farm_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($farms as $farm)
                        <option value="{{ $farm->id }}" {{ old('farm_id') == $farm->id ? 'selected' : '' }}>{{ $farm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="needed_by">Needed By</label>
                <input type="date" id="needed_by" name="needed_by" value="{{ old('needed_by') }}" class="form-input">
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="notes">Notes</label>
                <input type="text" id="notes" name="notes" value="{{ old('notes') }}" class="form-input">
            </div>
        </div>
        <p class="page-subtitle" style="margin: 12px 0;">You'll add the items needed on the next screen.</p>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Create Request</button>
            <a href="{{ route('procurement-requests.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
