@extends('layouts.app')
@section('title', 'Edit Crop Program')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Crop Program</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('crop-programs.update', $cropProgram) }}" method="POST">
        @csrf
        @method('PUT')
        @include('crop-programs._form', ['program' => $cropProgram])
        <div style="display: flex; gap: 12px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('crop-programs.show', $cropProgram) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
