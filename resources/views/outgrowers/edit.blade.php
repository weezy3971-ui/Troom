@extends('layouts.app')
@section('title', 'Edit Outgrower')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Outgrower</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('outgrowers.update', $outgrower) }}" method="POST">
        @csrf
        @method('PUT')
        @include('outgrowers._form', ['outgrower' => $outgrower])
        <div style="display: flex; gap: 12px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('outgrowers.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
