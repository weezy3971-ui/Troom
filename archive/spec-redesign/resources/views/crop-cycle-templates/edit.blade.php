@extends('layouts.app')
@section('title', 'Edit Cycle Template')

@section('content')
@php $template = $cropCycleTemplate; @endphp
<x-crumb-nav />
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Template</h1>
        <p class="page-subtitle">{{ $template->label() }}</p>
    </div>
</div>

@if($template->cropCycles()->count() > 0)
    <div class="alert alert-warning">
        {{ $template->cropCycles()->count() }} crop cycle(s) are running this template. Changes take effect on their remaining schedule; work already logged is untouched.
    </div>
@endif

<div class="card" style="max-width: 760px;">
    <form action="{{ route('crop-cycle-templates.update', $template) }}" method="POST">
        @method('PUT')
        @include('crop-cycle-templates._form')
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('crop-cycle-templates.show', $template) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
