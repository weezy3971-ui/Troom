@extends('layouts.app')
@section('title', 'Edit Irrigation Log')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('irrigation-logs.index') }}">Irrigation</a> <span>/</span>
    <a href="{{ route('irrigation-logs.show', $irrigationLog) }}">Log #{{ $irrigationLog->id }}</a> <span>/</span> <span>Edit</span>
</div>
<div class="page-header"><h1 class="page-title">Edit Irrigation Log</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('irrigation-logs.update', $irrigationLog) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="block_id">Block *</label>
                <select id="block_id" name="block_id" class="form-select" required>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" {{ old('block_id', $irrigationLog->block_id) == $block->id ? 'selected' : '' }}>{{ $block->name }} — {{ $block->farm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="pump_asset_id">Pump Asset</label>
                <select id="pump_asset_id" name="pump_asset_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($pumps as $pump)
                        <option value="{{ $pump->id }}" {{ old('pump_asset_id', $irrigationLog->pump_asset_id) == $pump->id ? 'selected' : '' }} {{ $pump->status !== 'operational' && $irrigationLog->pump_asset_id != $pump->id ? 'disabled' : '' }}>
                            {{ $pump->name }}@if($pump->status !== 'operational') (not operational)@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="log_date">Date *</label>
                <input type="date" id="log_date" name="log_date" value="{{ old('log_date', $irrigationLog->log_date->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="hours">Hours *</label>
                <input type="number" step="0.1" id="hours" name="hours" value="{{ old('hours', $irrigationLog->hours) }}" class="form-input" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="start_time">Start Time</label>
                <input type="time" id="start_time" name="start_time" value="{{ old('start_time', $irrigationLog->start_time) }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="end_time">End Time</label>
                <input type="time" id="end_time" name="end_time" value="{{ old('end_time', $irrigationLog->end_time) }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="water_volume">Water Volume (L)</label>
                <input type="number" step="0.1" id="water_volume" name="water_volume" value="{{ old('water_volume', $irrigationLog->water_volume) }}" class="form-input" min="0">
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('irrigation-logs.show', $irrigationLog) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
