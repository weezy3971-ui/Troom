@extends('layouts.app')
@section('title', 'New Customer')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('customers.index') }}">Customers</a> <span>/</span> <span>New Customer</span>
</div>
<div class="page-header"><h1 class="page-title">Register Customer</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('customers.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="name">Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="contact">Contact</label>
                <input type="text" id="contact" name="contact" value="{{ old('contact') }}" class="form-input" placeholder="Phone / email">
            </div>
            <div class="form-group">
                <label class="form-label" for="price_list">Price List</label>
                <input type="text" id="price_list" name="price_list" value="{{ old('price_list') }}" class="form-input" placeholder="e.g. Export 2026">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="contract_terms">Contract Terms</label>
            <textarea id="contract_terms" name="contract_terms" class="form-textarea">{{ old('contract_terms') }}</textarea>
        </div>
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Register Customer</button>
            <a href="{{ route('customers.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
