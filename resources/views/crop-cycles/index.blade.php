@extends('layouts.app')
@section('title', 'Crop Cycles')

@section('content')
@php $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'crop_cycles'); @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">Crop Cycles</h1>
        <p class="page-subtitle">Plan and track each crop cycle per block and season</p>
    </div>
    @if($canWrite)<a href="{{ route('crop-cycles.create') }}" class="btn btn-primary">+ New Crop Cycle</a>@endif
</div>

<x-search-bar
    :action="route('crop-cycles.index')"
    placeholder="Search by season, block, farm, or crop…"
    :search="$search"
    :total="$cropCycles->count()"
    :filters="[
        ['name' => 'status', 'label' => 'All Statuses', 'options' => ['planned' => 'Planned', 'active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled']],
    ]"
/>

@if($cropCycles->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search || request('status'))
                <div class="icon">🔍</div>
                <h3>No crop cycles match your filters</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('crop-cycles.index') }}" class="btn btn-ghost">Clear Filters</a>
            @else
                <div class="icon">📅</div>
                <h3>No crop cycles planned</h3>
                <p>Create a crop cycle to start tracking planting, budgets, and harvests.</p>
                @if($canWrite)<a href="{{ route('crop-cycles.create') }}" class="btn btn-primary">+ New Crop Cycle</a>@endif
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Season</th>
                        <th>Block</th>
                        <th>Farm</th>
                        <th>Crop</th>
                        <th>Planting</th>
                        <th>Expected Harvest</th>
                        <th>Budget</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cropCycles as $cycle)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('crop-cycles.show', $cycle) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $cycle->season_name }}</a>
                        </td>
                        <td>{{ $cycle->block->name }}</td>
                        <td>{{ $cycle->block->farm->name }}</td>
                        <td>{{ $cycle->crop->name }}</td>
                        <td>{{ $cycle->planting_date?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ $cycle->expected_harvest_date?->format('M d, Y') ?? '—' }}</td>
                        <td>
                            @if($cycle->seasonalBudget)
                                KES {{ number_format($cycle->seasonalBudget->total_budget) }}
                            @else
                                <span style="color: var(--warning);">Not set</span>
                            @endif
                        </td>
                        <td><span class="badge badge-{{ $cycle->status }}">{{ ucfirst($cycle->status) }}</span></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('crop-cycles.show', $cycle) }}" class="btn btn-ghost btn-sm">View</a>
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
