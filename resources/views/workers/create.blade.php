@extends('layouts.app')
@section('title', 'Add Worker')

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('workers.index') }}">Workers</a> <span>/</span> <span>Add Worker</span>
</div>
<div class="page-header"><h1 class="page-title">Add Worker</h1></div>

<div class="card" style="max-width: 620px;">
    <form action="{{ route('workers.store') }}" method="POST">
        @csrf
        @include('workers._form', ['worker' => null])
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Add Worker</button>
            <a href="{{ route('workers.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
