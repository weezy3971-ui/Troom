@extends('layouts.app')
@section('title', 'Record Attendance')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Record Labour Attendance</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('labour-attendances.store') }}" method="POST">
        @csrf
        @include('labour-attendances._fields', ['attendance' => null])
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Record Attendance</button>
            <a href="{{ route('labour-attendances.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
