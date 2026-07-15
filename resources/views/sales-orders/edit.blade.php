@extends('layouts.app')
@section('title', 'Edit Sales Order')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Sales Order</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('sales-orders.update', $salesOrder) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="customer_id">Customer *</label>
                <select id="customer_id" name="customer_id" class="form-select" required>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id', $salesOrder->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="crop_id">Crop</label>
                <select id="crop_id" name="crop_id" class="form-select">
                    <option value="">— Select —</option>
                    @foreach($crops as $crop)
                        <option value="{{ $crop->id }}" {{ old('crop_id', $salesOrder->crop_id) == $crop->id ? 'selected' : '' }}>{{ $crop->name }} ({{ $crop->variety }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="order_date">Order Date *</label>
                <input type="date" id="order_date" name="order_date" value="{{ old('order_date', $salesOrder->order_date->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="delivery_date">Delivery Date</label>
                <input type="date" id="delivery_date" name="delivery_date" value="{{ old('delivery_date', $salesOrder->delivery_date?->toDateString()) }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="requested_quantity">Requested Quantity (kg) *</label>
                <input type="number" step="0.01" id="requested_quantity" name="requested_quantity" value="{{ old('requested_quantity', $salesOrder->requested_quantity) }}" class="form-input" min="0" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status *</label>
                <select id="status" name="status" class="form-select" required>
                    @foreach(['pending', 'allocated', 'dispatched', 'fulfilled', 'cancelled'] as $s)
                        <option value="{{ $s }}" {{ old('status', $salesOrder->status) == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('sales-orders.show', $salesOrder) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
