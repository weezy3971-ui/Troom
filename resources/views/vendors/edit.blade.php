@extends('layouts.app')
@section('title', 'Edit Vendor')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Vendor</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('vendors.update', $vendor) }}" method="POST">
        @csrf
        @method('PUT')
        @include('vendors._form')
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('vendors.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
