@extends('layouts.app')
@section('title', 'Edit Project')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Project</h1></div>

<div class="card" style="max-width: 860px;">
    <form action="{{ route('projects.update', $project) }}" method="POST">
        @csrf @method('PUT')
        @include('projects._form', ['project' => $project])
        <div style="display: flex; gap: 12px; margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
    <form action="{{ route('projects.destroy', $project) }}" method="POST" data-confirm="Delete this project, its tasks, assignments and cost rows?" style="margin-top: 20px;">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">Delete Project</button>
    </form>
</div>
@endsection
