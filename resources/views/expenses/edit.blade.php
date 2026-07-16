@extends('layouts.app')
@section('title', 'Edit Expense')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Expense</h1></div>

<div class="card" style="max-width: 640px;">
    <form action="{{ route('expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="category">Category *</label>
                <select id="category" name="category" class="form-select" required>
                    <option value="">Select category</option>
                    @foreach($categories as $c)
                        <option value="{{ $c }}" {{ old('category', $expense->category) === $c ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $c)) }}</option>
                    @endforeach
                </select>
                @error('category') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="amount">Amount (KES) *</label>
                <input type="number" step="0.01" id="amount" name="amount" value="{{ old('amount', $expense->amount) }}" class="form-input" min="0.01" required>
                @error('amount') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="payment_mode">Mode of Payment</label>
                <select id="payment_mode" name="payment_mode" class="form-select">
                    <option value="">Select mode</option>
                    @foreach($paymentModes as $mode)
                        <option value="{{ $mode }}" {{ old('payment_mode', $expense->payment_mode) === $mode ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $mode)) }}</option>
                    @endforeach
                </select>
                @error('payment_mode') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="expense_date">Date *</label>
                <input type="date" id="expense_date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="farm_id">Farm</label>
                <select id="farm_id" name="farm_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($farms as $farm)
                        <option value="{{ $farm->id }}" {{ old('farm_id', $expense->farm_id) == $farm->id ? 'selected' : '' }}>{{ $farm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="block_id">Block</label>
                <select id="block_id" name="block_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($blocks as $block)
                        <option value="{{ $block->id }}" {{ old('block_id', $expense->block_id) == $block->id ? 'selected' : '' }}>{{ $block->name }} — {{ $block->farm->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="description">Description *</label>
            <textarea id="description" name="description" class="form-textarea" required>{{ old('description', $expense->description) }}</textarea>
            @error('description') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="receipt">Receipt Photo</label>
            @if($expense->receipt_path)
                <div style="margin-bottom: 8px;">
                    <a href="{{ $expense->receiptUrl() }}" target="_blank" rel="noopener">
                        <img src="{{ $expense->receiptUrl() }}" alt="Current receipt" style="max-width: 160px; border-radius: 8px; border: 1px solid var(--border); display: block;">
                    </a>
                </div>
            @endif
            <input type="file" id="receipt" name="receipt" class="form-input" accept="image/*">
            <p style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">
                {{ $expense->receipt_path ? 'Take or choose a new photo to replace the current receipt.' : 'Take a photo of the receipt or choose one from your gallery.' }} JPG/PNG, up to 5MB.
            </p>
            @error('receipt') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('expenses.show', $expense) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
