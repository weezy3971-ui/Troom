@extends('layouts.app')
@section('title', 'Edit Attendance')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('labour-attendances.index') }}">Labour</a> <span>/</span>
    <a href="{{ route('labour-attendances.show', $labourAttendance) }}">Attendance #{{ $labourAttendance->id }}</a> <span>/</span> <span>Edit</span>
</div>
<div class="page-header"><h1 class="page-title">Edit Labour Attendance</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('labour-attendances.update', $labourAttendance) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="attendance_date">Date *</label>
                <input type="date" id="attendance_date" name="attendance_date" value="{{ old('attendance_date', $labourAttendance->attendance_date->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="worker_name">Worker Name *</label>
                <input type="text" id="worker_name" name="worker_name" value="{{ old('worker_name', $labourAttendance->worker_name) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="task">Task *</label>
                <input type="text" id="task" name="task" value="{{ old('task', $labourAttendance->task) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="block_id">Block</label>
                <select id="block_id" name="block_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" {{ old('block_id', $labourAttendance->block_id) == $block->id ? 'selected' : '' }}>{{ $block->name }} — {{ $block->farm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="crop_cycle_id">Crop Cycle</label>
                <select id="crop_cycle_id" name="crop_cycle_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($cropCycles as $cycle)
                        <option value="{{ $cycle->id }}" {{ old('crop_cycle_id', $labourAttendance->crop_cycle_id) == $cycle->id ? 'selected' : '' }}>{{ $cycle->season_name }} — {{ $cycle->block->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="hours_worked">Hours Worked *</label>
                <input type="number" step="0.1" id="hours_worked" name="hours_worked" value="{{ old('hours_worked', $labourAttendance->hours_worked) }}" class="form-input" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="rate">Rate (KES/hour) *</label>
                <input type="number" step="0.01" id="rate" name="rate" value="{{ old('rate', $labourAttendance->rate) }}" class="form-input" min="0" required>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('labour-attendances.show', $labourAttendance) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
