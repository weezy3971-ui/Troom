@extends('layouts.app')
@section('title', 'New Cycle Template')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">New Cycle Template</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('crop-cycle-templates.store') }}" method="POST">
        @include('crop-cycle-templates._form')
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Create Template</button>
            <a href="{{ route('crop-cycle-templates.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
        <p style="font-size:12px; color:var(--text-muted); margin-top:12px;">
            You'll add the growth stages and the spray/input schedule next.
        </p>
    </form>
</div>
@endsection
