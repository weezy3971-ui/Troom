@extends('layouts.app')
@section('title', 'New Project')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">New Project</h1></div>

<div class="card" style="max-width: 860px;">
    <form action="{{ route('projects.store') }}" method="POST">
        @csrf
        @include('projects._form', ['project' => null])
        <div style="display: flex; gap: 12px; margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Create Project</button>
            <a href="{{ route('projects.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
