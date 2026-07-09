@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back, {{ auth()->user()->name }}</p>
    </div>
</div>

{{-- Compact KPI Bar — clickable chips --}}
<div class="kpi-bar">
    <a href="{{ route('farms.index') }}" class="kpi-chip">
        <span class="kpi-chip-dot olive"></span>
        <span class="kpi-chip-label">Farms</span>
        <span class="kpi-chip-value olive">{{ \App\Models\Farm::count() }}</span>
    </a>
    <a href="{{ route('blocks.index') }}" class="kpi-chip">
        <span class="kpi-chip-dot" style="background: var(--text-muted);"></span>
        <span class="kpi-chip-label">Blocks</span>
        <span class="kpi-chip-value">{{ \App\Models\Block::count() }}</span>
    </a>
    <a href="{{ route('crops.index') }}" class="kpi-chip">
        <span class="kpi-chip-dot" style="background: var(--text-muted);"></span>
        <span class="kpi-chip-label">Varieties</span>
        <span class="kpi-chip-value">{{ \App\Models\Crop::count() }}</span>
    </a>
    <a href="{{ route('crop-cycles.index') }}" class="kpi-chip">
        <span class="kpi-chip-dot success"></span>
        <span class="kpi-chip-label">Active</span>
        <span class="kpi-chip-value success">{{ \App\Models\CropCycle::where('status', 'active')->count() }}</span>
    </a>
    <a href="{{ route('crop-cycles.index') }}" class="kpi-chip">
        <span class="kpi-chip-dot warning"></span>
        <span class="kpi-chip-label">Planned</span>
        <span class="kpi-chip-value warning">{{ \App\Models\CropCycle::where('status', 'planned')->count() }}</span>
    </a>
    <a href="{{ route('assets.index') }}" class="kpi-chip">
        <span class="kpi-chip-dot terracotta"></span>
        <span class="kpi-chip-label">Assets</span>
        <span class="kpi-chip-value terracotta">{{ \App\Models\Asset::count() }}</span>
    </a>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    {{-- Recent Crop Cycles --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Crop Cycles</h3>
            <a href="{{ route('crop-cycles.index') }}" class="btn btn-ghost btn-sm">View All</a>
        </div>
        @php $recentCycles = \App\Models\CropCycle::with('block.farm', 'crop')->latest()->take(5)->get(); @endphp
        @if($recentCycles->isEmpty())
            <div class="empty-state" style="padding: 30px;">
                <p>No crop cycles yet.</p>
                <a href="{{ route('crop-cycles.create') }}" class="btn btn-primary btn-sm">Create First Cycle</a>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Block</th>
                            <th>Crop</th>
                            <th>Season</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentCycles as $cycle)
                        <tr>
                            <td>{{ $cycle->block->name }}</td>
                            <td>{{ $cycle->crop->name }}</td>
                            <td>{{ $cycle->season_name }}</td>
                            <td><span class="badge badge-{{ $cycle->status }}">{{ ucfirst($cycle->status) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Farms Overview --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Farms Overview</h3>
            <a href="{{ route('farms.index') }}" class="btn btn-ghost btn-sm">View All</a>
        </div>
        @php $farms = \App\Models\Farm::withCount('blocks', 'assets')->get(); @endphp
        @if($farms->isEmpty())
            <div class="empty-state" style="padding: 30px;">
                <p>No farms registered yet.</p>
                <a href="{{ route('farms.create') }}" class="btn btn-primary btn-sm">Add First Farm</a>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Farm</th>
                            <th>Location</th>
                            <th>Blocks</th>
                            <th>Assets</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($farms as $farm)
                        <tr>
                            <td><a href="{{ route('farms.show', $farm) }}" style="color: var(--olive); text-decoration: none; font-weight: 500;">{{ $farm->name }}</a></td>
                            <td>{{ $farm->location }}</td>
                            <td>{{ $farm->blocks_count }}</td>
                            <td>{{ $farm->assets_count }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
