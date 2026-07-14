@extends('layouts.app')
@section('title', 'Edit Guide')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Guide</h1></div>

<div class="card" style="max-width: 620px;">
    <form action="{{ route('guides.update', $guide) }}" method="POST">
        @csrf @method('PUT')
        @include('guides._form', ['guide' => $guide])
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('guides.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
    <form action="{{ route('guides.destroy', $guide) }}" method="POST" data-confirm="Remove this guide?" style="margin-top: 20px;">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">Remove Guide</button>
    </form>
</div>
@endsection
