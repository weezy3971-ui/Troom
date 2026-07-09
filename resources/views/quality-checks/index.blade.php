@extends('layouts.app')
@section('title', 'Quality Assurance')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Quality Assurance</h1>
        <p class="page-subtitle">Independent verification of packed lots before dispatch</p>
    </div>
    <a href="{{ route('quality-checks.create') }}" class="btn btn-primary">+ New Check</a>
</div>

<x-search-bar
    :action="route('quality-checks.index')"
    placeholder="Search by result, lot number, or trace code…"
    :search="$search"
    :total="$checks->count()"
/>

@if($checks->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No checks match "{{ $search }}"</h3>
                <a href="{{ route('quality-checks.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">🔬</div>
                <h3>No quality checks</h3>
                <p>Inspect packed lots against crop parameters and record pass/fail.</p>
                <a href="{{ route('quality-checks.create') }}" class="btn btn-primary">+ New Check</a>
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
                        <th>Lot</th>
                        <th>Result</th>
                        <th>Inspector</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($checks as $check)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('quality-checks.show', $check) }}" style="color: var(--olive); text-decoration: none;">{{ $check->check_date->format('M d, Y') }}</a>
                        </td>
                        <td>{{ $check->packhouseLot->lot_number ?? '—' }}</td>
                        <td><span class="badge {{ $check->result === 'pass' ? 'badge-active' : 'badge-down' }}">{{ ucfirst($check->result) }}</span></td>
                        <td>{{ $check->inspector->name ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('quality-checks.show', $check) }}" class="btn btn-ghost btn-sm">View</a>
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
