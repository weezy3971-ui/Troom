@extends('layouts.app')
@section('title', 'Log Irrigation')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Log Irrigation Session</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('irrigation-logs.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="block_id">Block *</label>
                <select id="block_id" name="block_id" class="form-select" required>
                    <option value="">Select block</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" {{ old('block_id') == $block->id ? 'selected' : '' }}>{{ $block->name }} — {{ $block->farm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="pump_asset_id">Pump Asset</label>
                <select id="pump_asset_id" name="pump_asset_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($pumps as $pump)
                        <option value="{{ $pump->id }}" {{ old('pump_asset_id') == $pump->id ? 'selected' : '' }} {{ $pump->status !== 'operational' ? 'disabled' : '' }}>
                            {{ $pump->name }}@if($pump->status !== 'operational') (not operational)@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="log_date">Date *</label>
                <input type="date" id="log_date" name="log_date" value="{{ old('log_date', now()->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="start_time">Start Time</label>
                <input type="time" id="start_time" name="start_time" value="{{ old('start_time') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="end_time">End Time</label>
                <input type="time" id="end_time" name="end_time" value="{{ old('end_time') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="hours">Hours * <span id="hours-hint" style="font-size:11px; color: var(--text-muted); font-weight:400;"></span></label>
                <input type="number" step="0.01" id="hours" name="hours" value="{{ old('hours') }}" class="form-input" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="water_volume">Water Volume (L)</label>
                <input type="number" step="0.1" id="water_volume" name="water_volume" value="{{ old('water_volume') }}" class="form-input" min="0">
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Log Session</button>
            <a href="{{ route('irrigation-logs.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<script>
(function () {
    const start  = document.getElementById('start_time');
    const end    = document.getElementById('end_time');
    const hours  = document.getElementById('hours');
    const hint   = document.getElementById('hours-hint');

    function calcHours() {
        if (!start.value || !end.value) return;
        const [sh, sm] = start.value.split(':').map(Number);
        const [eh, em] = end.value.split(':').map(Number);
        let diff = (eh * 60 + em) - (sh * 60 + sm);
        if (diff <= 0) return; // overnight or invalid
        const computed = (diff / 60).toFixed(2);
        hours.value = computed;
        hint.textContent = '(auto-calculated from times)';
    }

    start.addEventListener('change', calcHours);
    end.addEventListener('change', calcHours);
    hours.addEventListener('input', function () {
        hint.textContent = '(manually set)';
    });
}());
</script>
@endsection
