@extends('layouts.app')
@section('title', 'Generate Report')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('ai-reports.index') }}">AI Reports</a> <span>/</span> <span>Generate</span>
</div>
<div class="page-header"><h1 class="page-title">Generate Report</h1></div>

@unless($configured)
    <div class="alert alert-warning" style="margin-bottom:16px;">
        AI is not configured. Set <code>ANTHROPIC_API_KEY</code> in your <code>.env</code> first — generation will otherwise fail with a clear message.
    </div>
@endunless

<div class="card" style="max-width:640px;">
    <form action="{{ route('ai-reports.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label class="form-label" for="type">Report Type *</label>
            <select id="type" name="type" class="form-select" required>
                @foreach(\App\Models\AiReport::TYPES as $value => $label)
                    <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('type') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="period_start">Period Start</label>
                <input type="date" id="period_start" name="period_start" value="{{ old('period_start') }}" class="form-input">
                @error('period_start') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="period_end">Period End</label>
                <input type="date" id="period_end" name="period_end" value="{{ old('period_end') }}" class="form-input">
                @error('period_end') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="form-hint" style="margin-top:4px;">The report is written from your latest KPI snapshots, trends, and active alerts. Leave dates blank for a to-date summary.</p>
        <div style="display:flex; gap:12px; margin-top:16px;">
            <button type="submit" class="btn btn-primary">Generate</button>
            <a href="{{ route('ai-reports.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
