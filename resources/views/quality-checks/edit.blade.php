@extends('layouts.app')
@section('title', 'Edit Quality Check')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('quality-checks.index') }}">Quality Assurance</a> <span>/</span>
    <a href="{{ route('quality-checks.show', $qualityCheck) }}">Check #{{ $qualityCheck->id }}</a> <span>/</span> <span>Edit</span>
</div>
<div class="page-header"><h1 class="page-title">Edit Quality Check</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('quality-checks.update', $qualityCheck) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="packhouse_lot_id">Packhouse Lot *</label>
                <select id="packhouse_lot_id" name="packhouse_lot_id" class="form-select" required>
                    @foreach($lots as $lot)
                        <option value="{{ $lot->id }}" {{ old('packhouse_lot_id', $qualityCheck->packhouse_lot_id) == $lot->id ? 'selected' : '' }}>{{ $lot->lot_number }} — {{ $lot->traceability_code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="check_date">Check Date *</label>
                <input type="date" id="check_date" name="check_date" value="{{ old('check_date', $qualityCheck->check_date->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="result">Result *</label>
                <select id="result" name="result" class="form-select" required>
                    <option value="pass" {{ old('result', $qualityCheck->result) == 'pass' ? 'selected' : '' }}>Pass</option>
                    <option value="fail" {{ old('result', $qualityCheck->result) == 'fail' ? 'selected' : '' }}>Fail</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="inspector_id">Inspector</label>
                <select id="inspector_id" name="inspector_id" class="form-select">
                    <option value="">— Select —</option>
                    @foreach($inspectors as $user)
                        <option value="{{ $user->id }}" {{ old('inspector_id', $qualityCheck->inspector_id) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="parameters">Parameters (one "key: value" per line)</label>
            <textarea id="parameters" name="parameters" class="form-textarea">{{ old('parameters', collect($qualityCheck->parameters ?? [])->map(fn($v, $k) => "$k: $v")->implode("\n")) }}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label" for="photo_path">Photo reference</label>
            <input type="text" id="photo_path" name="photo_path" value="{{ old('photo_path', $qualityCheck->photo_path) }}" class="form-input">
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('quality-checks.show', $qualityCheck) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
