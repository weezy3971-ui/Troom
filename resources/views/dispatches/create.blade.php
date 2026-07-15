@extends('layouts.app')
@section('title', 'Schedule Dispatch')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Schedule Dispatch</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('dispatches.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="sales_order_id">Sales Order *</label>
                <select id="sales_order_id" name="sales_order_id" class="form-select" required>
                    <option value="">Select order</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}" {{ old('sales_order_id') == $order->id ? 'selected' : '' }}>#{{ $order->id }} — {{ $order->customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="dispatch_date">Dispatch Date *</label>
                <input type="date" id="dispatch_date" name="dispatch_date" value="{{ old('dispatch_date', now()->toDateString()) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="vehicle_asset_id">Vehicle</label>
                <select id="vehicle_asset_id" name="vehicle_asset_id" class="form-select">
                    <option value="">— Select —</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" {{ old('vehicle_asset_id') == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="driver_id">Driver</label>
                <select id="driver_id" name="driver_id" class="form-select">
                    <option value="">— Select —</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="route">Route</label>
                <input type="text" id="route" name="route" value="{{ old('route') }}" class="form-input" placeholder="e.g. Naivasha → Nairobi JKIA">
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status *</label>
                <select id="status" name="status" class="form-select" required>
                    @foreach(['scheduled', 'in_transit', 'delivered', 'cancelled'] as $s)
                        <option value="{{ $s }}" {{ old('status', 'scheduled') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Schedule Dispatch</button>
            <a href="{{ route('dispatches.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
