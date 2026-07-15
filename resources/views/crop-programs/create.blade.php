@extends('layouts.app')
@section('title', 'New Crop Program')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">New Crop Program</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('crop-programs.store') }}" method="POST">
        @csrf
        @include('crop-programs._form', ['program' => null])
        <div style="display: flex; gap: 12px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">Create Program</button>
            <a href="{{ route('crop-programs.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
