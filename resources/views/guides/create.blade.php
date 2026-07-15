@extends('layouts.app')
@section('title', 'Add Guide')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Add Guide</h1></div>

<div class="card" style="max-width: 620px;">
    <form action="{{ route('guides.store') }}" method="POST">
        @csrf
        @include('guides._form', ['guide' => null])
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Add Guide</button>
            <a href="{{ route('guides.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
