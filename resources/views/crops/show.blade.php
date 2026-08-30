@extends('layouts.app')
@section('title', $crop->name)

@section('content')
@php
    $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'master_data');
    $cycles = $crop->cropCycles->sortByDesc(fn ($c) => $c->planting_date ?? $c->created_at);
    $active = $cycles->where('status', 'active');
    $counts = [
        'all' => $cycles->count(),
        'active' => $active->count(),
        'planned' => $cycles->where('status', 'planned')->count(),
        'completed' => $cycles->where('status', 'completed')->count(),
    ];
    $acres = $active->sum(fn ($c) => (float) ($c->block->size_acres ?? 0));
@endphp

<style>
    .cy-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
    .cy-filter {
        font: inherit; font-size: 12px; line-height: 1; cursor: pointer; padding: 6px 11px; border-radius: 999px;
        border: 1px solid var(--border-strong); background: transparent; color: var(--text-secondary);
    }
    .cy-filter:hover { background: var(--bg-secondary); }
    .cy-filter[aria-pressed="true"] { background: var(--text-primary); border-color: var(--text-primary); color: var(--bg-card); }
    .cy-row.is-hidden { display: none; }
    .cy-sub { font-size: 11.5px; color: var(--text-muted); }
    .cy-over { color: var(--danger-text); font-weight: 600; }

    /* Date-aware stage planner (replaces the flat progress bar) */
    .cy-planner-row > td { padding: 2px 16px 20px; border-top: none; }
    .planner { position: relative; margin: 0 46px; max-width: 640px; min-height: 56px; }
    .planner-line { position: absolute; top: 11px; left: 0; right: 0; height: 3px; background: var(--bg-secondary); border-radius: 999px; }
    .planner-line span { display: block; height: 100%; background: var(--olive); border-radius: 999px; transition: width .3s ease; }
    .planner-today { position: absolute; top: -1px; height: 26px; width: 2px; background: var(--accent-hover); transform: translateX(-50%); }
    .planner-today::after {
        content: "Today"; position: absolute; top: -15px; left: 50%; transform: translateX(-50%);
        font-size: 9.5px; font-weight: 600; letter-spacing: .02em; color: var(--accent-hover); white-space: nowrap;
    }
    .planner-stage { position: absolute; top: 0; transform: translateX(-50%); width: 88px; text-align: center; }
    .planner-dot {
        display: flex; align-items: center; justify-content: center; width: 22px; height: 22px; margin: 0 auto;
        border-radius: 50%; border: 2px solid var(--border-strong); background: var(--bg-card);
        font-size: 12px; line-height: 1; color: transparent;
    }
    .planner-stage.done .planner-dot { background: var(--olive); border-color: var(--olive); color: #fff; }
    .planner-stage.current .planner-dot { border-color: var(--olive); color: var(--olive); box-shadow: 0 0 0 3px var(--bg-secondary); }
    .planner-name { display: block; margin-top: 7px; font-size: 11px; font-weight: 600; color: var(--text-secondary); white-space: nowrap; }
    .planner-stage.todo .planner-name { font-weight: 500; color: var(--text-muted); }
    .planner-date { display: block; font-size: 10.5px; color: var(--text-muted); }
</style>

<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $crop->name }}</h1>
        <p class="page-subtitle">{{ $crop->variety ?? 'No variety specified' }} - {{ $crop->crop_type }}</p>
    </div>
    <div class="actions">
        @if($canWrite)
            <a href="{{ route('crops.edit', $crop) }}" class="btn btn-secondary">Edit</a>
        @endif
        @if(\App\Support\ModuleAccess::allows(auth()->user(), 'crop_cycles'))
            <a href="{{ route('crop-cycles.create') }}" class="btn btn-primary">+ New Cycle</a>
        @endif
    </div>
</div>

{{-- Catalogue facts (Master Data) --}}
<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Crop Type</div>
        <div class="detail-value">{{ $crop->crop_type }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Variety</div>
        <div class="detail-value">{{ $crop->variety ?? '-' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Days to Maturity</div>
        <div class="detail-value">{{ $crop->days_to_maturity ? $crop->days_to_maturity . ' days' : '-' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Expected Yield / Acre</div>
        <div class="detail-value">{{ $crop->expected_yield_per_acre ? number_format($crop->expected_yield_per_acre) . ' kg' : '-' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Active Cycles</div>
        <div class="detail-value">{{ $counts['active'] }}{{ $acres > 0 ? ' - ' . number_format($acres, 1) . ' acres' : '' }}</div>
    </div>
</div>

{{-- Cycles of this crop: block, season, the two dates, status and budget --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Cycles of this Crop ({{ $counts['all'] }})</h3>
    </div>

    @if($cycles->isEmpty())
        <div class="empty-state" style="padding: 30px;">
            <p>This crop hasn't been planted yet. Create a crop cycle to plan it into a block and season.</p>
        </div>
    @else
        <div class="cy-filters">
            <button type="button" class="cy-filter" data-filter="all" aria-pressed="true">All ({{ $counts['all'] }})</button>
            @foreach(['active' => 'Active', 'planned' => 'Planned', 'completed' => 'Completed'] as $value => $label)
                @if($counts[$value] ?? 0)
                    <button type="button" class="cy-filter" data-filter="{{ $value }}" aria-pressed="false">{{ $label }} ({{ $counts[$value] }})</button>
                @endif
            @endforeach
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Season</th>
                        <th>Block</th>
                        <th>Planting</th>
                        <th>Expected Harvest</th>
                        <th>Budget vs Spend</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($cycles as $cycle)
                    @php
                        $progress = $cycle->progressPercent();
                        $days = $cycle->daysToHarvest();
                        $budget = (float) optional($cycle->seasonalBudget)->total_budget;
                        $spend = $cycle->actualCost();

                        // Derive a stage planner from the planting -> harvest span. Each
                        // stage sits at its real calendar date and is ticked once that
                        // date is in the past, matching the current date.
                        $stages = null;
                        if ($cycle->planting_date && $cycle->expected_harvest_date) {
                            $span = max(1, $cycle->planting_date->diffInDays($cycle->expected_harvest_date));
                            $today = now()->startOfDay();
                            $stages = [];
                            foreach ([
                                ['Planting', 0.00], ['Establishment', 0.15], ['Vegetative', 0.40],
                                ['Flowering', 0.65], ['Maturity', 0.85], ['Harvest', 1.00],
                            ] as [$label, $frac]) {
                                $date = $cycle->planting_date->copy()->addDays((int) round($frac * $span));
                                $stages[] = ['label' => $label, 'pos' => $frac * 100, 'date' => $date, 'done' => $today->gte($date)];
                            }
                            // The current stage is the last one already reached (active cycles only).
                            $lastDone = -1;
                            foreach ($stages as $i => $s) { if ($s['done']) { $lastDone = $i; } }
                            foreach ($stages as $i => &$s) {
                                $s['state'] = $cycle->status === 'active' && $i === $lastDone && $i < count($stages) - 1
                                    ? 'current'
                                    : ($s['done'] ? 'done' : 'todo');
                            }
                            unset($s);
                        }
                    @endphp
                    <tr class="cy-row" data-status="{{ $cycle->status }}">
                        <td style="font-weight:600;">
                            <a href="{{ route('crop-cycles.show', $cycle) }}" style="color: var(--accent-hover); text-decoration:none;">{{ $cycle->season_name }}</a>
                        </td>
                        <td>
                            <a href="{{ route('blocks.show', $cycle->block) }}" style="color: var(--accent-hover); text-decoration:none;">{{ $cycle->block->name }}</a>
                            <div class="cy-sub">{{ $cycle->block->farm->name }}{{ $cycle->block->size_acres ? ' - ' . number_format($cycle->block->size_acres, 1) . ' ac' : '' }}</div>
                        </td>
                        <td>{{ $cycle->planting_date?->format('M d, Y') ?? '-' }}</td>
                        <td>
                            {{ $cycle->expected_harvest_date?->format('M d, Y') ?? '-' }}
                            @if(! is_null($days) && $cycle->status === 'active')
                                <div class="cy-sub">
                                    {{ $days > 0 ? $days . ' days to go' : ($days === 0 ? 'due today' : abs($days) . ' days overdue') }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($budget > 0)
                                <span class="mono">KES {{ number_format($budget) }}</span>
                                <div class="cy-sub {{ $spend > $budget ? 'cy-over' : '' }}">
                                    spent {{ number_format($spend) }}{{ $spend > $budget ? ' - over budget' : '' }}
                                </div>
                            @else
                                <span style="color: var(--text-muted);">Not budgeted</span>
                                @if($cycle->status === 'planned')
                                    <div class="cy-sub">needed before activation</div>
                                @endif
                            @endif
                        </td>
                        <td><span class="badge badge-{{ $cycle->status }}">{{ ucfirst($cycle->status) }}</span></td>
                        <td style="text-align:right;">
                            <a href="{{ route('crop-cycles.show', $cycle) }}" class="btn btn-ghost btn-sm">View</a>
                        </td>
                    </tr>
                    @if($stages)
                        <tr class="cy-row cy-planner-row" data-status="{{ $cycle->status }}">
                            <td colspan="7">
                                <div class="planner">
                                    <div class="planner-line"><span style="width: {{ $progress ?? 0 }}%;"></span></div>
                                    @if($cycle->status === 'active' && ! is_null($progress))
                                        <div class="planner-today" style="left: {{ $progress }}%;" title="Today"></div>
                                    @endif
                                    @foreach($stages as $stage)
                                        <div class="planner-stage {{ $stage['state'] }}" style="left: {{ $stage['pos'] }}%;">
                                            <div class="planner-dot">@if($stage['done'])&#10003;@endif</div>
                                            <span class="planner-name">{{ $stage['label'] }}</span>
                                            <span class="planner-date">{{ $stage['date']->format('M d') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
    // Filter in place so the page stays one screen as cycles accumulate.
    document.querySelectorAll('.cy-filter').forEach(function (button) {
        button.addEventListener('click', function () {
            var wanted = button.dataset.filter;

            document.querySelectorAll('.cy-filter').forEach(function (other) {
                other.setAttribute('aria-pressed', String(other === button));
            });

            document.querySelectorAll('.cy-row').forEach(function (row) {
                row.classList.toggle('is-hidden', wanted !== 'all' && row.dataset.status !== wanted);
            });
        });
    });
</script>
@endsection
