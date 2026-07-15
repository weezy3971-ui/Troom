@extends('layouts.app')
@section('title', 'Add Outgrower')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Add Outgrower</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('outgrowers.store') }}" method="POST">
        @csrf
        @include('outgrowers._form', ['outgrower' => null])
        <div style="display: flex; gap: 12px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">Add Outgrower</button>
            <a href="{{ route('outgrowers.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
