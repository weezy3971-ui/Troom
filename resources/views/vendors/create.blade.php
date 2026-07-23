@extends('layouts.app')
@section('title', 'New Vendor')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Register Vendor</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('vendors.store') }}" method="POST">
        @csrf
        @include('vendors._form')
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Register Vendor</button>
            <a href="{{ route('vendors.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
