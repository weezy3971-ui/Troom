@extends('layouts.app')
@section('title', $cropCycle->season_name)

@section('content')
@php $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'crop_cycles'); @endphp
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $cropCycle->season_name }}</h1>
        <p class="page-subtitle">{{ $cropCycle->crop->name }} on {{ $cropCycle->block->name }} ({{ $cropCycle->block->farm->name }})</p>
    </div>
    @if($canWrite)
    <div class="actions">
        @if($cropCycle->status === 'planned')
            <form action="{{ route('crop-cycles.activate', $cropCycle) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">▶ Activate</button>
            </form>
        @endif
        @if($cropCycle->status === 'active')
            <form action="{{ route('crop-cycles.complete', $cropCycle) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">✓ Complete</button>
            </form>
        @endif
        @if(in_array($cropCycle->status, ['planned', 'active']))
            <form action="{{ route('crop-cycles.cancel', $cropCycle) }}" method="POST" data-confirm="Cancel this crop cycle?">
                @csrf
                <button type="submit" class="btn btn-danger">Cancel Cycle</button>
            </form>
        @endif
        <a href="{{ route('crop-cycles.edit', $cropCycle) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('crop-cycles.destroy', $cropCycle) }}" method="POST" data-confirm="Permanently delete this crop cycle? This cannot be undone.">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
    @endif
</div>

<x-help-panel title="Guide — crop cycle & schedule">
    <p><strong>Activating</strong> a cycle needs a seasonal budget and locks the block to this cycle. On activation, the crop's active stage program is copied in as a dated <strong>Stage Schedule</strong> (each stage = planting date + its day offset).</p>
    <p><strong>Stage Schedule:</strong> tick stages <em>Done</em> as the work happens. Pending stages that fall due raise a notification. If you add or change the crop program later, use <em>Regenerate</em> to rebuild the schedule.</p>
    <p><strong>Yield Projection</strong> needs planting detail (beds/area) plus the crop's expected yield and reference price to estimate harvest and revenue, then compares against what's actually harvested.</p>
</x-help-panel>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Status</div>
        <div class="detail-value"><span class="badge badge-{{ $cropCycle->status }}">{{ ucfirst($cropCycle->status) }}</span></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Block</div>
        <div class="detail-value"><a href="{{ route('blocks.show', $cropCycle->block) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $cropCycle->block->name }}</a></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Crop</div>
        <div class="detail-value"><a href="{{ route('crops.show', $cropCycle->crop) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $cropCycle->crop->name }}</a></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Planting Date</div>
        <div class="detail-value">{{ $cropCycle->planting_date?->format('M d, Y') ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Expected Harvest</div>
        <div class="detail-value">{{ $cropCycle->expected_harvest_date?->format('M d, Y') ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Farm</div>
        <div class="detail-value">{{ $cropCycle->block->farm->name }}</div>
    </div>
</div>

{{-- Seasonal Budget --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title">Seasonal Budget</h3>
    </div>

    @if($cropCycle->seasonalBudget)
        <div class="stats-grid" style="margin-bottom: 0;">
            <div class="stat-card">
                <div class="stat-label">Labour</div>
                <div class="stat-value" style="font-size: 20px;">KES {{ number_format($cropCycle->seasonalBudget->labour_budget) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Inputs</div>
                <div class="stat-value" style="font-size: 20px;">KES {{ number_format($cropCycle->seasonalBudget->input_budget) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Irrigation</div>
                <div class="stat-value" style="font-size: 20px;">KES {{ number_format($cropCycle->seasonalBudget->irrigation_budget) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Overhead</div>
                <div class="stat-value" style="font-size: 20px;">KES {{ number_format($cropCycle->seasonalBudget->overhead_budget) }}</div>
            </div>
            <div class="stat-card" style="border-color: rgba(99,102,241,0.3);">
                <div class="stat-label">Total Budget</div>
                <div class="stat-value accent" style="font-size: 20px;">KES {{ number_format($cropCycle->seasonalBudget->total_budget) }}</div>
            </div>
        </div>
    @endif

    @if($canWrite && in_array($cropCycle->status, ['planned', 'active']))
    @php
        // With no budget yet, pre-fill from the crop's reusable template (if any).
        $tpl = $cropCycle->seasonalBudget ? null : $cropCycle->crop;
        $usingTemplate = $tpl && $tpl->hasBudgetTemplate();
    @endphp
    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
        <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 4px;">{{ $cropCycle->seasonalBudget ? 'Update Budget' : 'Set Budget (required before activation)' }}</h4>
        @if($usingTemplate)
            <p style="font-size:12.5px; color:var(--text-muted); margin-bottom:16px;">Pre-filled from the <strong>{{ $cropCycle->crop->name }}</strong> budget template — adjust as needed.</p>
        @endif
        <form action="{{ route('crop-cycles.budget', $cropCycle) }}" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="labour_budget">Labour Budget (KES)</label>
                    <input type="number" id="labour_budget" name="labour_budget" value="{{ old('labour_budget', $cropCycle->seasonalBudget?->labour_budget ?? ($tpl?->default_labour_budget ?? 0)) }}" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="input_budget">Input Budget (KES)</label>
                    <input type="number" id="input_budget" name="input_budget" value="{{ old('input_budget', $cropCycle->seasonalBudget?->input_budget ?? ($tpl?->default_input_budget ?? 0)) }}" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="irrigation_budget">Irrigation Budget (KES)</label>
                    <input type="number" id="irrigation_budget" name="irrigation_budget" value="{{ old('irrigation_budget', $cropCycle->seasonalBudget?->irrigation_budget ?? ($tpl?->default_irrigation_budget ?? 0)) }}" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="overhead_budget">Overhead Budget (KES)</label>
                    <input type="number" id="overhead_budget" name="overhead_budget" value="{{ old('overhead_budget', $cropCycle->seasonalBudget?->overhead_budget ?? ($tpl?->default_overhead_budget ?? 0)) }}" class="form-input" step="0.01" min="0" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Budget</button>
        </form>
    </div>
    @endif
</div>

{{-- Budget vs. Actual --}}
@if($cropCycle->seasonalBudget)
@php
    $totalBudget  = (float) $cropCycle->seasonalBudget->total_budget;
    $actualCost   = $cropCycle->actualCost();
    $remaining    = $totalBudget - $actualCost;
    $pct          = $totalBudget > 0 ? min(round(($actualCost / $totalBudget) * 100, 1), 100) : 0;
    $overBudget   = $actualCost > $totalBudget;
@endphp
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title">Budget vs. Actual</h3>
        @if($overBudget)
            <span class="badge badge-down" style="margin-left: 10px;">⚠ Over Budget</span>
        @endif
    </div>
    <div style="padding: 16px 0 8px;">
        <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">
            <span>Spent: <strong style="color: {{ $overBudget ? 'var(--red, #ef4444)' : 'var(--text)' }};">KES {{ number_format($actualCost) }}</strong></span>
            <span>Budget: <strong>KES {{ number_format($totalBudget) }}</strong></span>
        </div>
        <div style="background: var(--border); border-radius: 6px; height: 10px; overflow: hidden;">
            <div style="height: 100%; width: {{ $pct }}%; background: {{ $overBudget ? '#ef4444' : 'var(--accent, #6366f1)' }}; border-radius: 6px; transition: width 0.4s ease;"></div>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); margin-top: 6px;">
            <span>{{ $pct }}% used</span>
            <span>{{ $overBudget ? 'Over by KES ' . number_format(abs($remaining)) : 'KES ' . number_format($remaining) . ' remaining' }}</span>
        </div>
    </div>
</div>
@endif

{{-- Yield Projection & Revenue --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title">Yield Projection &amp; Revenue</h3>
        @if($projection['basis'])
            <span class="badge badge-planned" style="margin-left: 10px;">{{ $projection['basis'] === 'per_bed' ? 'Per-bed basis' : 'Per-acre basis' }}</span>
        @endif
    </div>

    @if($projection['projected_yield'] === null)
        <p style="font-size: 13px; color: var(--text-muted); padding: 8px 0;">
            No projection yet. Record planting detail (beds or area) on this cycle and set the crop's
            expected yield{{ $projection['price_per_kg'] > 0 ? '' : ' and reference price' }} to see a projected harvest and revenue.
        </p>
    @else
    @php
        $germPct = round($projection['germination_rate'] * 100);
        $popPct = round($projection['population_rate'] * 100);
        $germMeasured = $projection['germination_source'] === 'measured';
        $popMeasured = $projection['population_source'] === 'measured';
    @endphp
        <div class="stats-grid" style="margin-bottom: 0;">
            <div class="stat-card">
                <div class="stat-label">Planted</div>
                <div class="stat-value" style="font-size: 20px;">
                    {{ $projection['planted_beds'] ? number_format($projection['planted_beds']) . ' beds' : number_format($projection['planted_area'], 2) . ' ac' }}
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Germination ({{ $germMeasured ? 'measured' : 'assumed' }})</div>
                <div class="stat-value" style="font-size: 20px;">{{ $germPct }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Plant Population ({{ $popMeasured ? 'measured' : 'assumed' }})</div>
                <div class="stat-value" style="font-size: 20px;">{{ $popPct }}%</div>
            </div>
            <div class="stat-card" style="border-color: rgba(99,102,241,0.3);">
                <div class="stat-label">Projected Yield</div>
                <div class="stat-value accent" style="font-size: 20px;">{{ number_format($projection['projected_yield']) }} kg</div>
            </div>
            @if($projection['projected_revenue'] !== null)
            <div class="stat-card" style="border-color: rgba(99,102,241,0.3);">
                <div class="stat-label">Projected Revenue</div>
                <div class="stat-value accent" style="font-size: 20px;">KES {{ number_format($projection['projected_revenue']) }}</div>
            </div>
            @endif
        </div>

        @if($projection['pre_harvest_forecast'] !== null)
        @php
            $fVar = $projection['forecast_variance'];
            $fAhead = $fVar !== null && $fVar >= 0;
        @endphp
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Pre-harvest Sample Forecast</h4>
            <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--text-muted);">
                <span>Latest sampling walk projects <strong style="color: var(--text);">{{ number_format($projection['pre_harvest_forecast']) }} kg</strong></span>
                @if($fVar !== null)
                <span style="color: {{ $fAhead ? 'var(--olive)' : 'var(--danger-text)' }}; font-weight: 600;">{{ $fAhead ? '▲ +' : '▼ ' }}{{ number_format($fVar * 100, 1) }}% vs actual</span>
                @endif
            </div>
        </div>
        @endif

        @if($projection['actual_yield'] > 0)
        @php
            $variance = $projection['yield_variance'];
            $ahead = $variance !== null && $variance >= 0;
        @endphp
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Projected vs. Actual</h4>
            <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">
                <span>Harvested so far: <strong style="color: var(--text);">{{ number_format($projection['actual_yield']) }} kg</strong></span>
                <span>Projected: <strong>{{ number_format($projection['projected_yield']) }} kg</strong></span>
            </div>
            @if($variance !== null)
            <div style="font-size: 12.5px; color: {{ $ahead ? 'var(--olive, #6b8e23)' : 'var(--danger-text, #ef4444)' }}; font-weight: 600;">
                {{ $ahead ? '▲' : '▼' }} {{ $ahead ? '+' : '' }}{{ number_format($variance * 100, 1) }}% vs projection
            </div>
            @endif
        </div>
        @endif

        <p style="font-size: 11.5px; color: var(--text-muted); margin-top: 16px;">
            Projection = {{ $projection['basis'] === 'per_bed' ? 'beds × expected yield/bed' : 'area × expected yield/acre' }}
            × {{ $germPct }}% germination × {{ $popPct }}% population{{ $projection['price_per_kg'] > 0 ? ', valued at KES ' . number_format($projection['price_per_kg'], 2) . '/kg' : '' }}.
            @if($germMeasured || $popMeasured)Rates use the latest field readings below.@else Germination and population are crop-default assumptions until you record field readings below.@endif
        </p>
    @endif
</div>

{{-- Theme 2: crop stage program schedule --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title">Stage Schedule</h3>
        @if($canSchedule)
            <form action="{{ route('crop-cycles.schedule', $cropCycle) }}" method="POST" style="margin-left:auto;" data-confirm="Rebuild the schedule from the crop's active program? Existing stages will be replaced.">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">{{ $cropCycle->stages->isEmpty() ? 'Generate Schedule' : 'Regenerate' }}</button>
            </form>
        @endif
    </div>

    @if($cropCycle->stages->isEmpty())
        <div class="alert alert-info" style="margin: 16px 20px;">
            @if($canSchedule)
                No stages scheduled yet. Click <strong>Generate Schedule</strong> to build the timeline from the crop's program.
            @else
                No schedule. Set a planting date on this cycle and define an active
                <a href="{{ route('crop-programs.index') }}">crop program</a> for {{ $cropCycle->crop?->name ?? 'this crop' }} to enable scheduling.
            @endif
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Stage</th><th>Activity</th><th>Due</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @foreach($cropCycle->stages as $stage)
                    <tr>
                        <td>{{ $stage->sequence }}</td>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            {{ $stage->name }}
                            @if($stage->notes)<div class="page-subtitle" style="margin:0; font-weight:400;">{{ $stage->notes }}</div>@endif
                        </td>
                        <td>{{ $stage->activity_type ? ucfirst(str_replace('_',' ', $stage->activity_type)) : '—' }}</td>
                        <td>{{ $stage->due_date->format('M d, Y') }}</td>
                        <td>
                            @if($stage->isDone())
                                <span class="badge badge-completed">Done</span>
                            @elseif($stage->status === 'skipped')
                                <span class="badge badge-neutral">Skipped</span>
                            @elseif($stage->isOverdue())
                                <span class="badge badge-cancelled">Overdue</span>
                            @elseif($stage->isDue())
                                <span class="badge badge-planned">Due today</span>
                            @else
                                <span class="badge badge-active">Pending</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                @if($stage->isDone())
                                    <form action="{{ route('crop-cycles.stages.update', [$cropCycle, $stage]) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="pending">
                                        <button type="submit" class="btn btn-ghost btn-sm">Reopen</button>
                                    </form>
                                @else
                                    <form action="{{ route('crop-cycles.stages.update', [$cropCycle, $stage]) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="done">
                                        <button type="submit" class="btn btn-secondary btn-sm">Mark Done</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ============ In-season crop monitoring ============ --}}

{{-- Germination checks --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header"><h3 class="card-title">Germination Checks</h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Date</th><th>Day</th><th>Sample</th><th>Germinated</th><th>Rate</th><th>Notes</th>@if($canWrite)<th></th>@endif</tr>
            </thead>
            <tbody>
                @forelse($cropCycle->germinationChecks->sortByDesc('check_date') as $g)
                <tr>
                    <td>{{ $g->check_date->format('M d, Y') }}</td>
                    <td class="mono">{{ $g->days_after_sowing !== null ? 'D+' . $g->days_after_sowing : '—' }}</td>
                    <td class="mono">{{ $g->sample_size }}</td>
                    <td class="mono">{{ $g->germinated_count }}</td>
                    <td class="mono"><strong>{{ number_format($g->germination_rate * 100, 1) }}%</strong></td>
                    <td style="color: var(--text-muted);">{{ $g->notes ?? '—' }}</td>
                    @if($canWrite)
                    <td style="text-align:right;">
                        <form action="{{ route('crop-cycles.germination.destroy', [$cropCycle, $g]) }}" method="POST" data-confirm="Remove this germination check?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
                        </form>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $canWrite ? 7 : 6 }}" style="text-align:center; color: var(--text-muted); padding: 20px;">No germination checks recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($canWrite)
    <div style="padding: 16px 22px; border-top: 1px solid var(--border);">
        <form action="{{ route('crop-cycles.germination.store', $cropCycle) }}" method="POST">
            @csrf
            <div class="form-grid" style="margin-bottom: 12px;">
                <div class="form-group" style="margin:0;"><label class="form-label" for="g_date">Check date *</label>
                    <input type="date" id="g_date" name="check_date" value="{{ old('check_date', now()->toDateString()) }}" class="form-input" required></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="g_days">Days after sowing</label>
                    <input type="number" id="g_days" name="days_after_sowing" value="{{ old('days_after_sowing') }}" class="form-input" min="0" placeholder="e.g. 5"></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="g_sample">Sample size *</label>
                    <input type="number" id="g_sample" name="sample_size" value="{{ old('sample_size') }}" class="form-input" min="1" required></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="g_germ">Germinated *</label>
                    <input type="number" id="g_germ" name="germinated_count" value="{{ old('germinated_count') }}" class="form-input" min="0" required></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="g_notes">Notes</label>
                    <input type="text" id="g_notes" name="notes" value="{{ old('notes') }}" class="form-input" placeholder="optional"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">+ Record Germination Check</button>
        </form>
    </div>
    @endif
</div>

{{-- Plant population (stand) counts --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header"><h3 class="card-title">Plant Population Counts</h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Date</th><th>Day</th><th>Population</th><th>Sample beds</th><th>Plants</th><th>Notes</th>@if($canWrite)<th></th>@endif</tr>
            </thead>
            <tbody>
                @forelse($cropCycle->plantPopulationCounts->sortByDesc('count_date') as $p)
                <tr>
                    <td>{{ $p->count_date->format('M d, Y') }}</td>
                    <td class="mono">{{ $p->days_after_planting !== null ? 'D+' . $p->days_after_planting : '—' }}</td>
                    <td class="mono"><strong>{{ number_format($p->population_rate * 100, 1) }}%</strong></td>
                    <td class="mono">{{ $p->sample_bed_count ?? '—' }}</td>
                    <td class="mono">{{ $p->plants_counted ?? '—' }}</td>
                    <td style="color: var(--text-muted);">{{ $p->notes ?? '—' }}</td>
                    @if($canWrite)
                    <td style="text-align:right;">
                        <form action="{{ route('crop-cycles.population.destroy', [$cropCycle, $p]) }}" method="POST" data-confirm="Remove this population count?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
                        </form>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $canWrite ? 7 : 6 }}" style="text-align:center; color: var(--text-muted); padding: 20px;">No population counts recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($canWrite)
    <div style="padding: 16px 22px; border-top: 1px solid var(--border);">
        <form action="{{ route('crop-cycles.population.store', $cropCycle) }}" method="POST">
            @csrf
            <div class="form-grid" style="margin-bottom: 12px;">
                <div class="form-group" style="margin:0;"><label class="form-label" for="p_date">Count date *</label>
                    <input type="date" id="p_date" name="count_date" value="{{ old('count_date', now()->toDateString()) }}" class="form-input" required></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="p_days">Days after planting</label>
                    <input type="number" id="p_days" name="days_after_planting" value="{{ old('days_after_planting') }}" class="form-input" min="0"></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="p_pct">Population (%) *</label>
                    <input type="number" step="0.1" id="p_pct" name="population_pct" value="{{ old('population_pct') }}" class="form-input" min="0" max="100" required placeholder="e.g. 85"></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="p_beds">Sample beds</label>
                    <input type="number" id="p_beds" name="sample_bed_count" value="{{ old('sample_bed_count') }}" class="form-input" min="0"></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="p_plants">Plants counted</label>
                    <input type="number" id="p_plants" name="plants_counted" value="{{ old('plants_counted') }}" class="form-input" min="0"></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="p_notes">Notes</label>
                    <input type="text" id="p_notes" name="notes" value="{{ old('notes') }}" class="form-input" placeholder="optional"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">+ Record Population Count</button>
        </form>
    </div>
    @endif
</div>

{{-- Pre-harvest yield forecast (sampling) --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header"><h3 class="card-title">Pre-harvest Yield Sampling</h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Date</th><th>Sample beds</th><th>Total beds</th><th>Sample yield</th><th>Projected total</th><th>Notes</th>@if($canWrite)<th></th>@endif</tr>
            </thead>
            <tbody>
                @forelse($cropCycle->yieldForecasts->sortByDesc('forecast_date') as $f)
                <tr>
                    <td>{{ $f->forecast_date->format('M d, Y') }}</td>
                    <td class="mono">{{ $f->sample_bed_count }}</td>
                    <td class="mono">{{ $f->total_bed_count }}</td>
                    <td class="mono">{{ number_format($f->sample_yield_kg, 2) }} kg</td>
                    <td class="mono"><strong>{{ number_format($f->projected_total_kg, 2) }} kg</strong></td>
                    <td style="color: var(--text-muted);">{{ $f->notes ?? '—' }}</td>
                    @if($canWrite)
                    <td style="text-align:right;">
                        <form action="{{ route('crop-cycles.forecast.destroy', [$cropCycle, $f]) }}" method="POST" data-confirm="Remove this forecast?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
                        </form>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $canWrite ? 7 : 6 }}" style="text-align:center; color: var(--text-muted); padding: 20px;">No pre-harvest samples recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($canWrite)
    <div style="padding: 16px 22px; border-top: 1px solid var(--border);">
        <form action="{{ route('crop-cycles.forecast.store', $cropCycle) }}" method="POST">
            @csrf
            <div class="form-grid" style="margin-bottom: 12px;">
                <div class="form-group" style="margin:0;"><label class="form-label" for="f_date">Sample date *</label>
                    <input type="date" id="f_date" name="forecast_date" value="{{ old('forecast_date', now()->toDateString()) }}" class="form-input" required></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="f_sbeds">Sample beds walked *</label>
                    <input type="number" id="f_sbeds" name="sample_bed_count" value="{{ old('sample_bed_count') }}" class="form-input" min="1" required placeholder="e.g. 10"></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="f_tbeds">Total beds *</label>
                    <input type="number" id="f_tbeds" name="total_bed_count" value="{{ old('total_bed_count') }}" class="form-input" min="1" required placeholder="e.g. 90"></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="f_kg">Sample yield (kg) *</label>
                    <input type="number" step="0.01" id="f_kg" name="sample_yield_kg" value="{{ old('sample_yield_kg') }}" class="form-input" min="0" required placeholder="kg from the sample beds"></div>
                <div class="form-group" style="margin:0;"><label class="form-label" for="f_notes">Notes</label>
                    <input type="text" id="f_notes" name="notes" value="{{ old('notes') }}" class="form-input" placeholder="optional"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">+ Record Pre-harvest Sample</button>
        </form>
    </div>
    @endif
</div>
@endsection
