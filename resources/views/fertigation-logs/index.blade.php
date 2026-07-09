@extends('layouts.app')
@section('title', 'Fertigation')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Fertigation Logs</h1>
        <p class="page-subtitle">Nutrient applications per crop cycle — each generates a cost allocation</p>
    </div>
    <a href="{{ route('fertigation-logs.create') }}" class="btn btn-primary">+ Log Application</a>
</div>

<x-search-bar
    :action="route('fertigation-logs.index')"
    placeholder="Search by nutrient, method, season, or block…"
    :search="$search"
    :total="$logs->count()"
/>

@if($logs->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No fertigation logs match "{{ $search }}"</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('fertigation-logs.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">🧪</div>
                <h3>No fertigation logged</h3>
                <p>Log nutrient applications to track cost per crop cycle.</p>
                <a href="{{ route('fertigation-logs.create') }}" class="btn btn-primary">+ Log Application</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Crop Cycle</th>
                        <th>Nutrient</th>
                        <th>Quantity</th>
                        <th>Method</th>
                        <th>Cost (KES)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('fertigation-logs.show', $log) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $log->log_date->format('M d, Y') }}</a>
                        </td>
                        <td>{{ $log->cropCycle->season_name }} — {{ $log->cropCycle->block->name }}</td>
                        <td>{{ $log->nutrient_type }}</td>
                        <td>{{ number_format($log->quantity, 2) }}</td>
                        <td>{{ $log->method ?? '—' }}</td>
                        <td>{{ number_format($log->cost) }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('fertigation-logs.show', $log) }}" class="btn btn-ghost btn-sm">View</a>
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
