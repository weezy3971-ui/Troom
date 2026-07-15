@extends('layouts.app')
@section('title', 'New Ride')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Book a Ride</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('rides.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="customer_name">Customer Name *</label>
                <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" class="form-input" required>
                @error('customer_name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="customer_phone">Customer Phone</label>
                <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="start_time">Start Time *</label>
                <input type="datetime-local" id="start_time" name="start_time" value="{{ old('start_time', now()->format('Y-m-d\TH:i')) }}" class="form-input" required>
                @error('start_time') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="duration_minutes">Duration (minutes) *</label>
                <input type="number" min="1" max="1440" id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', 120) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="amount">Amount (KES) *</label>
                <input type="number" step="0.01" min="0" id="amount" name="amount" value="{{ old('amount') }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="payment_status">Payment *</label>
                <select id="payment_status" name="payment_status" class="form-select" required>
                    <option value="paid" {{ old('payment_status', 'paid') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ old('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>
        </div>
        <div class="form-group" style="margin-top: 16px;">
            <label class="form-label" for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-textarea" rows="2">{{ old('notes') }}</textarea>
        </div>
        <p class="page-subtitle" style="margin: 12px 0;">A receipt number is generated on booking. The stable manager then assigns a horse and guide.</p>
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Book &amp; Generate Receipt</button>
            <a href="{{ route('rides.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
