@extends('layouts.app')
@section('title', 'Manual Weigh Reading')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Manual Weigh Reading</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('weigh-scale-readings.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="weighed_at">Weighed At *</label>
                <input type="datetime-local" id="weighed_at" name="weighed_at" value="{{ old('weighed_at', now()->format('Y-m-d\TH:i')) }}" class="form-input" required>
                @error('weighed_at') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="weighed_by_worker_id">Weighed By (roster)</label>
                <select id="weighed_by_worker_id" name="weighed_by_worker_id" class="form-select" data-worker-select>
                    {{-- Blank "none selected" option — must stay, or the browser
                         auto-selects the first worker and misattributes the reading. --}}
                    <option value=""></option>
                    @foreach($workers as $worker)
                        <option value="{{ $worker->id }}" data-name="{{ $worker->name }}" {{ old('weighed_by_worker_id') == $worker->id ? 'selected' : '' }}>{{ $worker->name }} ({{ ucfirst($worker->worker_type ?? 'casual') }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="weighed_by_name">Weighed By (name) *</label>
                <input type="text" id="weighed_by_name" name="weighed_by_name" value="{{ old('weighed_by_name') }}" class="form-input" required>
                @error('weighed_by_name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="item">Item Weighed *</label>
                <input type="text" id="item" name="item" value="{{ old('item') }}" class="form-input" placeholder="e.g. French beans crate" required>
                @error('item') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="weight">Weight *</label>
                <input type="number" step="0.001" id="weight" name="weight" value="{{ old('weight') }}" class="form-input" min="0" required>
                @error('weight') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="unit">Unit</label>
                <input type="text" id="unit" name="unit" value="{{ old('unit', 'kg') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="block_id">Block</label>
                <select id="block_id" name="block_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" {{ old('block_id') == $block->id ? 'selected' : '' }}>{{ $block->name }} — {{ $block->farm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="crop_cycle_id">Crop Cycle</label>
                <select id="crop_cycle_id" name="crop_cycle_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($cropCycles as $cycle)
                        <option value="{{ $cycle->id }}" {{ old('crop_cycle_id') == $cycle->id ? 'selected' : '' }}>{{ $cycle->season_name }} — {{ $cycle->block->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group" style="margin-top: 16px;">
            <label class="form-label" for="notes">Notes</label>
            <input type="text" id="notes" name="notes" value="{{ old('notes') }}" class="form-input">
        </div>
        <div style="display: flex; gap: 12px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">Save Reading</button>
            <a href="{{ route('weigh-scale-readings.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<script>
    (function () {
        var sel = document.querySelector('[data-worker-select]');
        var name = document.getElementById('weighed_by_name');
        if (!sel || !name) return;
        sel.addEventListener('change', function () {
            var opt = sel.options[sel.selectedIndex];
            var n = opt ? opt.getAttribute('data-name') : '';
            if (n) name.value = n;
        });
    })();
</script>
@endsection
