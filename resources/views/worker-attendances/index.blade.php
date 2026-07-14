@extends('layouts.app')
@section('title', 'Worker Attendance')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Worker Check-In / Out</h1>
        <p class="page-subtitle">Who is on site, when they arrived, and their hours once checked out</p>
    </div>
    <a href="{{ route('worker-attendances.create') }}" class="btn btn-primary">+ Check In Worker</a>
</div>

<div style="display:flex; gap:8px; margin-bottom:16px;">
    <a href="{{ route('worker-attendances.index', ['filter' => 'onsite']) }}" class="btn btn-sm {{ $filter === 'onsite' ? 'btn-secondary' : 'btn-ghost' }}">On Site</a>
    <a href="{{ route('worker-attendances.index', ['filter' => 'all']) }}" class="btn btn-sm {{ $filter === 'all' ? 'btn-secondary' : 'btn-ghost' }}">All History</a>
</div>

<x-search-bar
    :action="route('worker-attendances.index')"
    placeholder="Search by worker name…"
    :search="$search"
    :total="$attendances->count()"
/>

@if($attendances->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">🕒</div>
            <h3>{{ $filter === 'onsite' ? 'Nobody is checked in' : 'No attendance records' }}</h3>
            <p>Check a worker in to record their arrival.</p>
            <a href="{{ route('worker-attendances.create') }}" class="btn btn-primary">+ Check In Worker</a>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Worker</th><th>Date</th><th>Checked In</th><th>Checked Out</th><th>Hours</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($attendances as $att)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $att->worker?->name ?? '—' }}</td>
                        <td>{{ $att->work_date->format('M d, Y') }}</td>
                        <td>{{ $att->checked_in_at->format('H:i') }}</td>
                        <td>{{ $att->checked_out_at?->format('H:i') ?? '—' }}</td>
                        <td>{{ $att->hoursWorked() !== null ? number_format($att->hoursWorked(), 2) : '—' }}</td>
                        <td>
                            @if($att->isCheckedOut())
                                <span class="badge badge-completed">Checked Out</span>
                            @else
                                <span class="badge badge-active">On Site</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                @unless($att->isCheckedOut())
                                <form action="{{ route('worker-attendances.checkout', $att) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm">Check Out</button>
                                </form>
                                @endunless
                                <form action="{{ route('worker-attendances.destroy', $att) }}" method="POST" data-confirm="Delete this attendance record?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
