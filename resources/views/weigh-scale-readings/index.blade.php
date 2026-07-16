@extends('layouts.app')
@section('title', 'Weigh Scale')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Weigh Scale Notifications</h1>
        <p class="page-subtitle">Live feed from the digital scale — who weighed what, and the weight</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Weigh Scale">
            <p><strong>Live feed:</strong> Readings pushed by the integrated digital scale appear here automatically.</p>
            <p><strong>Acknowledge</strong> readings after reviewing them to clear them from the 'New' filter.</p>
            <p><strong>Manual readings</strong> can be added if the scale integration is offline.</p>
            <p><strong>Quick steps:</strong> Click "New" to see unreviewed readings → click "Acknowledge" on each once you've confirmed the weight.</p>
        </x-help-panel>
        <a href="{{ route('weigh-scale-readings.create') }}" class="btn btn-primary">+ Manual Reading</a>
    </div>
</div>

<div style="display:flex; gap:8px; margin-bottom:16px;">
    <a href="{{ route('weigh-scale-readings.index', ['filter' => 'new']) }}" class="btn btn-sm {{ $filter === 'new' ? 'btn-secondary' : 'btn-ghost' }}">
        New @if($newCount)<span class="badge badge-cancelled" style="margin-left:6px;">{{ $newCount }}</span>@endif
    </a>
    <a href="{{ route('weigh-scale-readings.index', ['filter' => 'all']) }}" class="btn btn-sm {{ $filter === 'all' ? 'btn-secondary' : 'btn-ghost' }}">All History</a>
</div>

<x-search-bar
    :action="route('weigh-scale-readings.index')"
    placeholder="Search by who weighed, item, or device…"
    :search="$search"
    :total="$readings->count()"
/>

@if($readings->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">⚖️</div>
            <h3>{{ $filter === 'new' ? 'No new readings' : 'No weigh readings yet' }}</h3>
            <p>Readings pushed by the digital scale appear here. You can also add one manually.</p>
            <a href="{{ route('weigh-scale-readings.create') }}" class="btn btn-primary">+ Manual Reading</a>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Weighed At</th><th>Weighed By</th><th>Item</th><th>Weight</th><th>Device</th><th>Source</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($readings as $reading)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $reading->weighed_at->format('M d, Y H:i') }}</td>
                        <td>
                            {{ $reading->weighed_by_name }}
                            @if($reading->weighedByWorker)<div class="page-subtitle" style="margin:0; font-weight:400;">Roster: {{ $reading->weighedByWorker->name }}</div>@endif
                        </td>
                        <td>{{ $reading->item }}</td>
                        <td class="mono" style="font-weight:600;">{{ number_format($reading->weight, 3) }} {{ $reading->unit }}</td>
                        <td>{{ $reading->device_name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $reading->source === 'device' ? 'badge-planned' : 'badge-neutral' }}">{{ ucfirst($reading->source) }}</span>
                        </td>
                        <td>
                            @if($reading->isAcknowledged())
                                <span class="badge badge-completed">Reviewed</span>
                            @else
                                <span class="badge badge-active">New</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                @unless($reading->isAcknowledged())
                                <form action="{{ route('weigh-scale-readings.acknowledge', $reading) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm">Acknowledge</button>
                                </form>
                                @endunless
                                <form action="{{ route('weigh-scale-readings.destroy', $reading) }}" method="POST" data-confirm="Delete this reading?">
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
