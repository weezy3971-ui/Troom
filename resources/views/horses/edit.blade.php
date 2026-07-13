@extends('layouts.app')
@section('title', 'Edit Horse')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('horses.index') }}">Horses</a> <span>/</span> <span>Edit {{ $horse->name }}</span>
</div>
<div class="page-header"><h1 class="page-title">Edit Horse</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('horses.update', $horse) }}" method="POST">
        @csrf @method('PUT')
        @include('horses._form', ['horse' => $horse])
        <div style="display: flex; gap: 12px; margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('horses.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
    <form action="{{ route('horses.destroy', $horse) }}" method="POST" data-confirm="Remove this horse?" style="margin-top: 20px;">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">Remove Horse</button>
    </form>
</div>
@endsection
