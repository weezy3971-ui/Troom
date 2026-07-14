@extends('layouts.app')
@section('title', 'Edit Worker')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Worker</h1></div>

<div class="card" style="max-width: 620px;">
    <form action="{{ route('workers.update', $worker) }}" method="POST">
        @csrf @method('PUT')
        @include('workers._form', ['worker' => $worker])
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('workers.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
    <form action="{{ route('workers.destroy', $worker) }}" method="POST" data-confirm="Remove this worker?" style="margin-top: 20px;">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">Remove Worker</button>
    </form>
</div>
@endsection
