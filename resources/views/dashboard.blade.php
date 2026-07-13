@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $u = auth()->user();
    $ma = \App\Support\ModuleAccess::class;
    $toneColor = [
        'success' => 'var(--success-text)',
        'warning' => 'var(--warning-text)',
        'danger'  => 'var(--danger-text)',
        'info'    => 'var(--info-text)',
        'muted'   => 'var(--text-muted)',
    ];
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Welcome back, {{ $u->name }}</h1>
        <p class="page-subtitle">{{ $u->roleLabel() }} · {{ now()->format('l, M j, Y') }}</p>
    </div>
</div>


{{-- Your focus: role-tailored counters --}}
@if(!empty($focus))
<h3 style="font-family: var(--font-display); font-size: 15px; margin-bottom: 12px; color: var(--text-secondary);">Your focus today</h3>
<div class="stats-grid" style="margin-bottom: 24px;">
    @foreach($focus as $item)
        <a href="{{ $item['url'] }}" class="stat-card has-icon" style="text-decoration: none;">
            <span class="stat-icon {{ $item['tone'] }}"><x-icon :name="$item['icon'] ?? 'dashboard'" solid /></span>
            <span class="stat-body">
                <span class="stat-label" style="display:block;">{{ $item['label'] }}</span>
                <span class="stat-value" style="display:block; color: {{ $toneColor[$item['tone']] ?? 'var(--text-primary)' }};">{{ $item['value'] }}</span>
                <span style="display:block; font-size: 12px; color: var(--text-muted); margin-top: 2px;">{{ $item['sub'] }}</span>
            </span>
        </a>
    @endforeach
</div>
@endif

{{-- Compact KPI Bar — only chips the user can reach --}}
<div class="kpi-bar">
    @if($ma::allows($u, 'master_data'))
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
    @endif
    @if($ma::allows($u, 'crop_cycles') || $ma::allows($u, 'master_data'))
    <a href="{{ route('crop-cycles.index', ['status' => 'active']) }}" class="kpi-chip">
        <span class="kpi-chip-dot success"></span>
        <span class="kpi-chip-label">Active</span>
        <span class="kpi-chip-value success">{{ \App\Models\CropCycle::where('status', 'active')->count() }}</span>
    </a>
    <a href="{{ route('crop-cycles.index', ['status' => 'planned']) }}" class="kpi-chip">
        <span class="kpi-chip-dot warning"></span>
        <span class="kpi-chip-label">Planned</span>
        <span class="kpi-chip-value warning">{{ \App\Models\CropCycle::where('status', 'planned')->count() }}</span>
    </a>
    @endif
    @if($ma::allows($u, 'master_data'))
    <a href="{{ route('assets.index') }}" class="kpi-chip">
        <span class="kpi-chip-dot terracotta"></span>
        <span class="kpi-chip-label">Assets</span>
        <span class="kpi-chip-value terracotta">{{ \App\Models\Asset::count() }}</span>
    </a>
    @endif
</div>

@if($ma::allows($u, 'crop_cycles') || $ma::allows($u, 'master_data'))
<div class="dashboard-cols" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
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
                    <thead><tr><th>Block</th><th>Crop</th><th>Season</th><th>Status</th></tr></thead>
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
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Farm</th><th>Location</th><th>Blocks</th><th>Assets</th></tr></thead>
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
@endif
@endsection
