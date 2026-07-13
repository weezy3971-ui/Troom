@extends('layouts.app')
@section('title', 'Log Harvest')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('harvest-batches.index') }}">Harvest</a> <span>/</span> <span>Log Harvest</span>
</div>
<div class="page-header"><h1 class="page-title">Log Harvest Batch</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('harvest-batches.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="crop_cycle_id">Crop Cycle *</label>
                <select id="crop_cycle_id" name="crop_cycle_id" class="form-select" required>
                    <option value="">Select crop cycle</option>
                    @foreach($cropCycles as $cycle)
                        <option value="{{ $cycle->id }}" {{ old('crop_cycle_id') == $cycle->id ? 'selected' : '' }}>{{ $cycle->season_name }} — {{ $cycle->block->name ?? '' }} ({{ $cycle->crop->name ?? '' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="block_id">Block</label>
                <select id="block_id" name="block_id" class="form-select">
                    <option value="">— From crop cycle —</option>
                    @foreach($cropCycles->pluck('block')->filter()->unique('id') as $block)
                        <option value="{{ $block->id }}" {{ old('block_id') == $block->id ? 'selected' : '' }}>{{ $block->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="harvest_date">Harvest Date *</label>
                <input type="date" id="harvest_date" name="harvest_date" value="{{ old('harvest_date', now()->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity_kg">Quantity (kg) *</label>
                <input type="number" step="0.01" id="quantity_kg" name="quantity_kg" value="{{ old('quantity_kg') }}" class="form-input" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="quality_grade">Quality Grade</label>
                <select id="quality_grade" name="quality_grade" class="form-select">
                    <option value="">—</option>
                    @foreach(['Grade A', 'Grade B', 'Grade C'] as $grade)
                        <option value="{{ $grade }}" {{ old('quality_grade') == $grade ? 'selected' : '' }}>{{ $grade }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="rejects_kg">Rejects (kg)</label>
                <input type="number" step="0.01" id="rejects_kg" name="rejects_kg" value="{{ old('rejects_kg', 0) }}" class="form-input" min="0">
            </div>
            <div class="form-group">
                <label class="form-label" for="harvested_by">Harvested By</label>
                <select id="harvested_by" name="harvested_by" class="form-select">
                    <option value="">— Select —</option>
                    @foreach($workers as $user)
                        <option value="{{ $user->id }}" {{ old('harvested_by') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Log Harvest</button>
            <a href="{{ route('harvest-batches.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<div id="phi-warning" class="alert alert-warning" style="display:none; max-width:760px; margin-top: 12px;">
    ⚠ <strong>Harvest blocked:</strong> The selected crop cycle has an active pre-harvest interval. Submission will be rejected until the PHI window clears.
</div>

<script>
(function () {
    const phiBlockedIds = @json($phiBlockedIds);
    const select  = document.getElementById('crop_cycle_id');
    const warning = document.getElementById('phi-warning');

    function check() {
        const selected = parseInt(select.value, 10);
        warning.style.display = phiBlockedIds.includes(selected) ? 'block' : 'none';
    }

    select.addEventListener('change', check);
    check();
}());
</script>
@endsection

