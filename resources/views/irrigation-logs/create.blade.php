@extends('layouts.app')
@section('title', 'Log Irrigation')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('irrigation-logs.index') }}">Irrigation</a> <span>/</span> <span>Log Session</span>
</div>
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
                <label class="form-label" for="hours">Hours *</label>
                <input type="number" step="0.1" id="hours" name="hours" value="{{ old('hours') }}" class="form-input" min="0" required>
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
@endsection
