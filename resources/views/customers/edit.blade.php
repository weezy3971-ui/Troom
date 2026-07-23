@extends('layouts.app')
@section('title', 'Edit Customer')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Customer</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('customers.update', $customer) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="name">Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="contact">Contact</label>
                <input type="text" id="contact" name="contact" value="{{ old('contact', $customer->contact) }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label" for="phone">M-Pesa Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-input" placeholder="0712 345 678">
                <p class="form-hint">Where payment prompts are sent. Saved as 2547…</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="price_list">Price List</label>
                <input type="text" id="price_list" name="price_list" value="{{ old('price_list', $customer->price_list) }}" class="form-input">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="contract_terms">Contract Terms</label>
            <textarea id="contract_terms" name="contract_terms" class="form-textarea">{{ old('contract_terms', $customer->contract_terms) }}</textarea>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
