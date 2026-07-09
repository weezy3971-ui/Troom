@extends('layouts.app')
@section('title', 'Packhouse')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Packhouse &amp; Traceability</h1>
        <p class="page-subtitle">Graded, packed lots with traceable codes</p>
    </div>
    <a href="{{ route('packhouse-lots.create') }}" class="btn btn-primary">+ New Lot</a>
</div>

<x-search-bar
    :action="route('packhouse-lots.index')"
    placeholder="Search by lot number, trace code, or packaging…"
    :search="$search"
    :total="$lots->count()"
/>

@if($lots->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No lots match "{{ $search }}"</h3>
                <a href="{{ route('packhouse-lots.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">📦</div>
                <h3>No packhouse lots</h3>
                <p>Pack accepted produce into traceable lots.</p>
                <a href="{{ route('packhouse-lots.create') }}" class="btn btn-primary">+ New Lot</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Lot #</th>
                        <th>Pack Date</th>
                        <th>Source Cycle</th>
                        <th>Quantity</th>
                        <th>Trace Code</th>
                        <th>Quality</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lots as $lot)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('packhouse-lots.show', $lot) }}" style="color: var(--olive); text-decoration: none;">{{ $lot->lot_number }}</a>
                        </td>
                        <td>{{ $lot->pack_date->format('M d, Y') }}</td>
                        <td>{{ $lot->harvestBatch->cropCycle->season_name ?? '—' }}</td>
                        <td class="mono">{{ number_format($lot->quantity_packed, 2) }} kg</td>
                        <td class="mono">{{ $lot->traceability_code }}</td>
                        <td>
                            @if($lot->isQualityPassed())
                                <span class="badge badge-active">Passed</span>
                            @elseif($lot->isQualityFailed())
                                <span class="badge badge-down">Failed</span>
                            @else
                                <span class="badge badge-neutral">Unchecked</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('packhouse-lots.show', $lot) }}" class="btn btn-ghost btn-sm">View</a>
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
