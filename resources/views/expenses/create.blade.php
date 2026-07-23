@extends('layouts.app')
@section('title', 'Log Expense')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Log Expense</h1></div>

<div class="card" style="max-width: 640px;">
    <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="category">Category *</label>
                <select id="category" name="category" class="form-select" required>
                    <option value="">Select category</option>
                    @foreach($categories as $c)
                        <option value="{{ $c }}" {{ old('category') === $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $c)) }}</option>
                    @endforeach
                </select>
                @error('category') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="amount">Amount (KES) *</label>
                <input type="number" step="0.01" id="amount" name="amount" value="{{ old('amount') }}" class="form-input" min="0.01" required>
                @error('amount') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="vendor_id">Paid To (Vendor)</label>
                <select id="vendor_id" name="vendor_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
                <p class="form-hint">Who received the money. Needed before this can be settled by M-Pesa.</p>
                @error('vendor_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="payment_mode">Mode of Payment</label>
                <select id="payment_mode" name="payment_mode" class="form-select">
                    <option value="">Select mode</option>
                    @foreach($paymentModes as $mode)
                        <option value="{{ $mode }}" {{ old('payment_mode') === $mode ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $mode)) }}</option>
                    @endforeach
                </select>
                @error('payment_mode') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="expense_date">Date *</label>
                <input type="date" id="expense_date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="farm_id">Farm</label>
                <select id="farm_id" name="farm_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($farms as $farm)
                        <option value="{{ $farm->id }}" {{ old('farm_id') == $farm->id ? 'selected' : '' }}>{{ $farm->name }}</option>
                    @endforeach
                </select>
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
                <label class="form-label" for="land_preparation_id">Land Preparation</label>
                <select id="land_preparation_id" name="land_preparation_id" class="form-select">
                    <option value="">&mdash; None &mdash;</option>
                    @foreach($landPreparations as $prep)
                        <option value="{{ $prep->id }}" {{ old('land_preparation_id') == $prep->id ? 'selected' : '' }}>{{ $prep->block->name }} &mdash; {{ $prep->block->farm->name }} ({{ $prep->statusLabel() }})</option>
                    @endforeach
                </select>
                <p class="form-hint" style="font-size:11.5px; color:var(--text-muted); margin-top:5px;">
                    Pick the preparation round this paid for &mdash; ploughing, manure, casual labour. It keeps the cost
                    with the planting that follows instead of leaving it unattached.
                </p>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="description">Description *</label>
            <textarea id="description" name="description" class="form-textarea" required placeholder="What was it for? e.g. new jembe for block 2, fuel for the pickup, county levy at the gate…">{{ old('description') }}</textarea>
            @error('description') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="receipt">Receipt Photo</label>
            <input type="file" id="receipt" name="receipt" class="form-input" accept="image/*">
            <p style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Take a photo of the receipt or choose one from your gallery. JPG/PNG, up to 5MB.</p>
            @error('receipt') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Log Expense</button>
            <a href="{{ route('expenses.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
