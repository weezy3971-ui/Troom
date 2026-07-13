@extends('layouts.app')
@section('title', 'Add Horse')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('horses.index') }}">Horses</a> <span>/</span> <span>Add Horse</span>
</div>
<div class="page-header"><h1 class="page-title">Add Horse</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('horses.store') }}" method="POST">
        @csrf
        @include('horses._form', ['horse' => null])
        <div style="display: flex; gap: 12px; margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Add Horse</button>
            <a href="{{ route('horses.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
