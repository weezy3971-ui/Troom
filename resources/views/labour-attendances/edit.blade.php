@extends('layouts.app')
@section('title', 'Edit Attendance')

@section('content')
<x-crumb-nav />
<div class="page-header"><h1 class="page-title">Edit Labour Attendance</h1></div>

<div class="card" style="max-width: 760px;">
    <form action="{{ route('labour-attendances.update', $labourAttendance) }}" method="POST">
        @csrf
        @method('PUT')
        @include('labour-attendances._fields', ['attendance' => $labourAttendance])
        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('labour-attendances.show', $labourAttendance) }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
