@extends('layouts.app')
@section('title', $cropProgram->name)

@section('content')
<x-crumb-nav />
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $cropProgram->name }}</h1>
        <p class="page-subtitle">
            {{ $cropProgram->crop?->name ?? '—' }}
            @if($cropProgram->description) · {{ $cropProgram->description }} @endif
        </p>
    </div>
    <div class="actions">
        <a href="{{ route('crop-programs.edit', $cropProgram) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('crop-programs.destroy', $cropProgram) }}" method="POST" data-confirm="Delete this program and all its stages?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="card" style="padding: 0; margin-bottom: 24px;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border);">
        <h3 style="margin: 0;">Stages ({{ $cropProgram->stages->count() }})</h3>
    </div>
    @if($cropProgram->stages->isEmpty())
        <div style="padding: 20px;"><p class="page-subtitle" style="margin:0;">No stages yet. Add the first stage below.</p></div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Stage</th><th>Activity</th><th>Day offset</th><th>Cadence</th><th>Default inputs</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($cropProgram->stages as $stage)
                    <tr>
                        <td>{{ $stage->sequence }}</td>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $stage->name }}</td>
                        <td>{{ $stage->activity_type ? ucfirst(str_replace('_',' ', $stage->activity_type)) : '—' }}</td>
                        <td>+{{ $stage->offset_days }} d</td>
                        <td>{{ $stage->cadence ?? '—' }}</td>
                        <td>{{ $stage->default_inputs ?? '—' }}</td>
                        <td>
                            <form action="{{ route('crop-programs.stages.destroy', [$cropProgram, $stage]) }}" method="POST" data-confirm="Remove this stage?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="card" style="max-width: 900px;">
    <h3 style="margin-top: 0;">Add Stage</h3>
    <form action="{{ route('crop-programs.stages.store', $cropProgram) }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="name">Stage Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-input" placeholder="e.g. First spray" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="activity_type">Activity Type</label>
                <select id="activity_type" name="activity_type" class="form-select">
                    <option value="">— None —</option>
                    @foreach($activityTypes as $type)
                        <option value="{{ $type }}" {{ old('activity_type') === $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="offset_days">Day Offset (from planting) *</label>
                <input type="number" id="offset_days" name="offset_days" value="{{ old('offset_days', 0) }}" class="form-input" min="0" required>
                @error('offset_days') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="cadence">Cadence</label>
                <input type="text" id="cadence" name="cadence" value="{{ old('cadence') }}" class="form-input" placeholder="e.g. weekly ×7">
            </div>
            <div class="form-group">
                <label class="form-label" for="default_inputs">Default Inputs</label>
                <input type="text" id="default_inputs" name="default_inputs" value="{{ old('default_inputs') }}" class="form-input" placeholder="e.g. fungicide + foliar feed">
            </div>
            <div class="form-group">
                <label class="form-label" for="sequence">Order</label>
                <input type="number" id="sequence" name="sequence" value="{{ old('sequence') }}" class="form-input" min="0" placeholder="Auto">
            </div>
        </div>
        <div style="margin-top: 12px;">
            <button type="submit" class="btn btn-primary">Add Stage</button>
        </div>
    </form>
</div>
@endsection
