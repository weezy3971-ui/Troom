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
    {{-- View toggle: Table / Timeline --}}
    <div style="display:flex; gap:4px; margin-bottom:14px;">
        <button class="hub-tab active" data-view="table" id="cc-tab-table">Table</button>
        <button class="hub-tab" data-view="timeline" id="cc-tab-timeline">Timeline</button>
    </div>

    {{-- TABLE VIEW --}}
    <div id="cc-view-table" class="card" style="padding: 0;">
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
                                <span style="color: var(--warning-text);">Not set</span>
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

    {{-- TIMELINE VIEW (CSS Gantt) --}}
    @php
        $dated = $cropCycles->filter(fn($c) => $c->planting_date && $c->expected_harvest_date);
        $min = $dated->min('planting_date');
        $max = $dated->max('expected_harvest_date');
        $spanDays = ($min && $max) ? max(1, $min->diffInDays($max)) : 1;
        $pos = function ($date) use ($min, $spanDays) {
            return max(0, min(100, $min->diffInDays($date) / $spanDays * 100));
        };
        $today = \Illuminate\Support\Carbon::today();
        $todayInRange = $min && $max && $today->betweenIncluded($min, $max);
        $barColor = ['active' => 'var(--success)', 'planned' => 'var(--info)', 'completed' => 'var(--text-muted)', 'cancelled' => 'var(--danger)'];
    @endphp
    <div id="cc-view-timeline" class="card" style="display:none;">
        @if($dated->isEmpty())
            <div class="empty-state" style="padding: 26px;"><p>No crop cycles have both a planting and expected-harvest date to plot.</p></div>
        @else
            <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--text-muted); margin-bottom:10px;">
                <span>{{ $min->format('M d, Y') }}</span>
                <span>Season timeline</span>
                <span>{{ $max->format('M d, Y') }}</span>
            </div>
            <div style="position:relative;">
                @if($todayInRange)
                    <div title="Today" style="position:absolute; top:0; bottom:0; left:calc(240px + (100% - 240px) * {{ $pos($today) / 100 }}); width:2px; background:var(--terracotta); z-index:2;"></div>
                @endif
                @foreach($dated->sortBy('planting_date') as $cycle)
                    @php $left = $pos($cycle->planting_date); $right = $pos($cycle->expected_harvest_date); $width = max(1.5, $right - $left); @endphp
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                        <div style="width:230px; flex-shrink:0; font-size:12.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <a href="{{ route('crop-cycles.show', $cycle) }}" style="color:var(--olive); text-decoration:none; font-weight:600;">{{ $cycle->crop->name }}</a>
                            <span style="color:var(--text-muted);">· {{ $cycle->block->name }}</span>
                        </div>
                        <div style="position:relative; flex:1; height:22px; background:var(--bg-secondary); border-radius:6px;">
                            <a href="{{ route('crop-cycles.show', $cycle) }}"
                               title="{{ $cycle->season_name }}: {{ $cycle->planting_date->format('M d') }} → {{ $cycle->expected_harvest_date->format('M d, Y') }}"
                               style="position:absolute; top:0; bottom:0; left:{{ $left }}%; width:{{ $width }}%; min-width:6px; background:{{ $barColor[$cycle->status] ?? 'var(--text-muted)' }}; border-radius:6px; opacity:0.9; display:flex; align-items:center; padding:0 8px; color:#fff; font-size:10.5px; font-weight:600; text-decoration:none; overflow:hidden; white-space:nowrap;">
                                {{ ucfirst($cycle->status) }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div style="display:flex; gap:16px; margin-top:14px; font-size:11px; color:var(--text-secondary); flex-wrap:wrap;">
                <span><span style="display:inline-block; width:10px; height:10px; background:var(--success); border-radius:2px; vertical-align:middle;"></span> Active</span>
                <span><span style="display:inline-block; width:10px; height:10px; background:var(--info); border-radius:2px; vertical-align:middle;"></span> Planned</span>
                <span><span style="display:inline-block; width:10px; height:10px; background:var(--text-muted); border-radius:2px; vertical-align:middle;"></span> Completed</span>
                <span><span style="display:inline-block; width:10px; height:10px; background:var(--danger); border-radius:2px; vertical-align:middle;"></span> Cancelled</span>
                @if($todayInRange)<span><span style="display:inline-block; width:2px; height:11px; background:var(--terracotta); vertical-align:middle;"></span> Today</span>@endif
            </div>
        @endif
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var tabs = { table: document.getElementById('cc-tab-table'), timeline: document.getElementById('cc-tab-timeline') };
        var views = { table: document.getElementById('cc-view-table'), timeline: document.getElementById('cc-view-timeline') };
        function show(which) {
            Object.keys(views).forEach(function (k) {
                views[k].style.display = (k === which) ? (k === 'table' ? 'block' : 'block') : 'none';
                tabs[k].classList.toggle('active', k === which);
            });
        }
        tabs.table.addEventListener('click', function () { show('table'); });
        tabs.timeline.addEventListener('click', function () { show('timeline'); });
    });
    </script>
@endif
@endsection
