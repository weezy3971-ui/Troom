@extends('layouts.app')
@section('title', 'Check Out Asset')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Check Out Asset</h1></div>

@if($available->isEmpty())
    <div class="card">
        <div class="alert alert-info" style="margin:0;">Every asset is currently checked out or none exist. <a href="{{ route('assets.index') }}">Manage assets</a> or check some back in first.</div>
    </div>
@else
<div class="card" style="max-width: 760px;">
    <form action="{{ route('checkouts.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="asset_id">Asset *</label>
                <select id="asset_id" name="asset_id" class="form-select" required>
                    <option value="">— Select —</option>
                    @foreach($available as $asset)
                        <option value="{{ $asset->id }}" {{ (string) old('asset_id', $selectedAsset) === (string) $asset->id ? 'selected' : '' }}>{{ $asset->name }} ({{ ucfirst($asset->type) }}@if($asset->farm) — {{ $asset->farm->name }}@endif)</option>
                    @endforeach
                </select>
                @error('asset_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="worker_id">Checked Out To (Labourer) *</label>
                <select id="worker_id" name="worker_id" class="form-select">
                    <option value="">— Select labourer —</option>
                    @foreach($workers as $worker)
                        <option value="{{ $worker->id }}" {{ (string) old('worker_id') === (string) $worker->id ? 'selected' : '' }}>{{ $worker->name }}</option>
                    @endforeach
                </select>
                @error('worker_id') <p class="form-error">{{ $message }}</p> @enderror
                <button type="button" class="btn btn-ghost btn-sm" data-reveal-new style="margin-top: 8px;" @if(old('new_worker_name')) hidden @endif>＋ Add new labourer</button>
            </div>
            <div class="form-group" data-new-worker @unless(old('new_worker_name')) hidden @endunless>
                <label class="form-label" for="new_worker_name">New Labourer Name *</label>
                <input type="text" id="new_worker_name" name="new_worker_name" value="{{ old('new_worker_name') }}" class="form-input" placeholder="Full name">
                @error('new_worker_name') <p class="form-error">{{ $message }}</p> @enderror
                <input type="text" name="new_worker_phone" value="{{ old('new_worker_phone') }}" class="form-input" placeholder="Phone (optional)" style="margin-top: 8px;">
                <button type="button" class="btn btn-ghost btn-sm" data-cancel-new style="margin-top: 8px;">Use existing labourer instead</button>
            </div>
            <div class="form-group">
                <label class="form-label" for="checked_out_at">Checked Out At *</label>
                <input type="datetime-local" id="checked_out_at" name="checked_out_at" value="{{ old('checked_out_at', now()->format('Y-m-d\TH:i')) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="expected_return_at">Expected Return</label>
                <input type="datetime-local" id="expected_return_at" name="expected_return_at" value="{{ old('expected_return_at') }}" class="form-input">
                @error('expected_return_at') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="form-group" style="margin-top: 16px;">
            <label class="form-label" for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-textarea" rows="2">{{ old('notes') }}</textarea>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">Check Out</button>
            <a href="{{ route('checkouts.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endif

<script>
    (function () {
        var select = document.getElementById('worker_id');
        var revealBtn = document.querySelector('[data-reveal-new]');
        var cancelBtn = document.querySelector('[data-cancel-new]');
        var block = document.querySelector('[data-new-worker]');
        var nameInput = document.getElementById('new_worker_name');
        if (!revealBtn || !block) return;

        function showNew() {
            block.hidden = false;
            revealBtn.hidden = true;
            if (select) { select.value = ''; select.removeAttribute('required'); }
            nameInput.setAttribute('required', 'required');
            nameInput.focus();
        }
        function showExisting() {
            block.hidden = true;
            revealBtn.hidden = false;
            nameInput.value = '';
            nameInput.removeAttribute('required');
        }
        revealBtn.addEventListener('click', showNew);
        if (cancelBtn) cancelBtn.addEventListener('click', showExisting);
    })();
</script>
@endsection
