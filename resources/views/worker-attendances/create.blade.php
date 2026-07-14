@extends('layouts.app')
@section('title', 'Check In Worker')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Check In Worker</h1></div>

@if($workers->isEmpty())
    <div class="card">
        <div class="alert alert-info" style="margin:0;">No active workers on the roster. <a href="{{ route('workers.create') }}">Add a worker</a> first.</div>
    </div>
@else
<div class="card" style="max-width: 640px;">
    <form action="{{ route('worker-attendances.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="worker_id">Worker *</label>
                <select id="worker_id" name="worker_id" class="form-select" required>
                    <option value="">— Select worker —</option>
                    @foreach($workers as $worker)
                        <option value="{{ $worker->id }}" {{ (string) old('worker_id') === (string) $worker->id ? 'selected' : '' }}>{{ $worker->name }} ({{ ucfirst($worker->worker_type ?? 'casual') }})</option>
                    @endforeach
                </select>
                @error('worker_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="checked_in_at">Checked In At *</label>
                <input type="datetime-local" id="checked_in_at" name="checked_in_at" value="{{ old('checked_in_at', now()->format('Y-m-d\TH:i')) }}" class="form-input" required>
                @error('checked_in_at') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="form-group" style="margin-top: 16px;">
            <label class="form-label" for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-textarea" rows="2">{{ old('notes') }}</textarea>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">Check In</button>
            <a href="{{ route('worker-attendances.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endif
@endsection
