@extends('layouts.app')
@section('title', 'Edit Ride ' . $ride->receipt_number)

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('rides.index') }}">Rides</a> <span>/</span>
    <a href="{{ route('rides.show', $ride) }}">{{ $ride->receipt_number }}</a> <span>/</span> <span>Edit</span>
</div>
<div class="page-header"><h1 class="page-title">Edit Ride</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('rides.update', $ride) }}" method="POST">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="customer_name">Customer Name *</label>
                <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $ride->customer_name) }}" class="form-input" required>
                @error('customer_name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="customer_phone">Customer Phone</label>
                <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $ride->customer_phone) }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="start_time">Start Time *</label>
                <input type="datetime-local" id="start_time" name="start_time" value="{{ old('start_time', $ride->start_time->format('Y-m-d\TH:i')) }}" class="form-input" required>
                @error('start_time') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="duration_minutes">Duration (minutes) *</label>
                <input type="number" min="1" max="1440" id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', $ride->duration_minutes) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="amount">Amount (KES) *</label>
                <input type="number" step="0.01" min="0" id="amount" name="amount" value="{{ old('amount', $ride->amount) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="payment_status">Payment *</label>
                <select id="payment_status" name="payment_status" class="form-select" required>
                    <option value="paid" {{ old('payment_status', $ride->payment_status) === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ old('payment_status', $ride->payment_status) === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>
        </div>
        <div class="form-group" style="margin-top: 16px;">
            <label class="form-label" for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-textarea" rows="2">{{ old('notes', $ride->notes) }}</textarea>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('rides.show', $ride) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
