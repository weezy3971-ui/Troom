@extends('layouts.app')
@section('title', $block->name)

@section('content')
@php
    $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'master_data');
    $activeCycle = $block->cropCycles->firstWhere('status', 'active');
@endphp

<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $block->name }}</h1>
        <p class="page-subtitle">
            <a href="{{ route('farms.show', $block->farm) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $block->farm->name }}</a>
            — {{ number_format($block->size_acres, 1) }} acres — {{ $block->soil_type ?? 'Soil type not set' }}
        </p>
    </div>
    @if($canWrite)
    <div class="actions">
        <a href="{{ route('blocks.edit', $block) }}" class="btn btn-secondary">Edit</a>
    </div>
    @endif
</div>

{{-- Snapshot --}}
<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Active Crop Cycle</div>
        <div class="detail-value">
            @if($activeCycle)
                <a href="{{ route('crop-cycles.show', $activeCycle) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $activeCycle->crop->name }} — {{ $activeCycle->season_name }}</a>
            @else
                <span style="color: var(--text-muted);">None</span>
            @endif
        </div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Crop Cycles</div>
        <div class="detail-value">{{ $counts['cycles'] }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Harvest Batches</div>
        <div class="detail-value">{{ $counts['harvest'] }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Irrigation Sessions</div>
        <div class="detail-value">{{ $counts['irrigation'] }}</div>
    </div>
</div>

{{-- Land preparation: the step between adding this block and planting into it --}}
@php $prep = $block->latestLandPreparation(); @endphp
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title">Land Preparation</h3>
        @if($prep)
            <span class="badge badge-{{ $prep->statusColor() }}">{{ $prep->statusLabel() }}</span>
        @endif
    </div>
    @if($prep)
        <div style="display:flex; flex-wrap:wrap; gap:24px; align-items:center;">
            <div style="font-size:13px; color:var(--text-secondary);">
                <strong>{{ $prep->doneCount() }} of {{ $prep->tasks->count() }}</strong> steps done
                @if($prep->completed_on)
                    — ready to plant {{ $prep->completed_on->format('M d, Y') }}
                @elseif($prep->started_on)
                    — started {{ $prep->started_on->format('M d, Y') }}
                @endif
            </div>
            <div style="font-size:13px; color:var(--text-secondary);">
                Cost: <strong>KES {{ number_format($prep->totalCost(), 2) }}</strong>
                @unless($prep->cropCycle)
                    <span style="color:var(--text-muted);">(not yet attributed to a planting)</span>
                @endunless
            </div>
            <div style="margin-left:auto; display:flex; gap:8px;">
                <a href="{{ route('land-preparations.show', $prep) }}" class="btn btn-secondary btn-sm">Open</a>
                @if(\App\Support\ModuleAccess::allows(auth()->user(), 'daily_ops'))
                    <form action="{{ route('land-preparations.store', $block) }}" method="POST"
                          data-confirm="Start a new preparation round on {{ $block->name }}? The current round stays on record."
                          data-confirm-ok="Start New Round">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">New round</button>
                    </form>
                @endif
            </div>
        </div>
    @else
        <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">
            <p style="font-size:13px; color:var(--text-secondary); margin:0;">
                This block hasn't been prepared yet. Preparation comes before planting — starting it here keeps
                the work, and what it costs, attached to the block.
            </p>
            <a href="{{ route('land-preparations.open', $block) }}" class="btn btn-primary btn-sm" style="margin-left:auto;">Prepare Block</a>
        </div>
    @endif
</div>

{{-- Tabbed hub --}}
<style>
    .hub-tabs { display: flex; flex-wrap: wrap; gap: 4px; border-bottom: 2px solid var(--border); margin-bottom: 0; }
    .hub-tab {
        appearance: none; background: none; border: none; cursor: pointer;
        font-family: inherit; font-size: 13.5px; font-weight: 600; color: var(--text-secondary);
        padding: 10px 14px; border-bottom: 3px solid transparent; margin-bottom: -2px;
        display: inline-flex; align-items: center; gap: 7px; white-space: nowrap;
    }
    .hub-tab:hover { color: var(--text-primary); }
    .hub-tab.active { color: var(--olive); border-bottom-color: var(--olive); }
    .hub-tab .count {
        font-size: 11px; font-weight: 700; background: var(--bg-secondary); color: var(--text-secondary);
        border-radius: 20px; padding: 1px 8px; min-width: 20px; text-align: center;
    }
    .hub-tab.active .count { background: var(--olive-bg); color: var(--olive); }
    .hub-panel { display: none; padding-top: 4px; }
    .hub-panel.active { display: block; animation: fadeIn 0.2s ease; }
</style>

<div class="card" style="margin-top: 20px;">
    <div class="hub-tabs" id="hub-tabs">
        <button class="hub-tab active" data-panel="cycles">Crop Cycles <span class="count">{{ $counts['cycles'] }}</span></button>
        <button class="hub-tab" data-panel="irrigation">Irrigation <span class="count">{{ $counts['irrigation'] }}</span></button>
        <button class="hub-tab" data-panel="fertigation">Fertigation <span class="count">{{ $counts['fertigation'] }}</span></button>
        <button class="hub-tab" data-panel="spray">Pest &amp; Spray <span class="count">{{ $counts['spray'] }}</span></button>
        <button class="hub-tab" data-panel="harvest">Harvest <span class="count">{{ $counts['harvest'] }}</span></button>
        <button class="hub-tab" data-panel="labour">Labour <span class="count">{{ $counts['labour'] }}</span></button>
        <button class="hub-tab" data-panel="activity">Daily Activity <span class="count">{{ $counts['activity'] }}</span></button>
    </div>

    {{-- Crop Cycles --}}
    <div class="hub-panel active" data-panel="cycles">
        @if($block->cropCycles->isEmpty())
            <div class="empty-state" style="padding: 26px;"><p>No crop cycles for this block yet.</p></div>
        @else
        <div class="table-wrap"><table>
            <thead><tr><th>Season</th><th>Crop</th><th>Planting</th><th>Expected Harvest</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach($block->cropCycles as $cycle)
                <tr>
                    <td>{{ $cycle->season_name }}</td>
                    <td>{{ $cycle->crop->name }}</td>
                    <td>{{ $cycle->planting_date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $cycle->expected_harvest_date?->format('M d, Y') ?? '—' }}</td>
                    <td><span class="badge badge-{{ $cycle->status }}">{{ ucfirst($cycle->status) }}</span></td>
                    <td style="text-align:right;"><a href="{{ route('crop-cycles.show', $cycle) }}" class="btn btn-ghost btn-sm">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @endif
    </div>

    {{-- Irrigation --}}
    <div class="hub-panel" data-panel="irrigation">
        @if($block->irrigationLogs->isEmpty())
            <div class="empty-state" style="padding: 26px;"><p>No irrigation logged for this block.</p></div>
        @else
        <div class="table-wrap"><table>
            <thead><tr><th>Date</th><th>Pump</th><th>Hours</th><th>Water (m³)</th></tr></thead>
            <tbody>
                @foreach($block->irrigationLogs as $log)
                <tr>
                    <td>{{ $log->log_date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $log->pump->name ?? '—' }}</td>
                    <td class="mono">{{ number_format($log->hours, 2) }}</td>
                    <td class="mono">{{ number_format($log->water_volume, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @endif
    </div>

    {{-- Fertigation --}}
    <div class="hub-panel" data-panel="fertigation">
        @if($block->fertigationLogs->isEmpty())
            <div class="empty-state" style="padding: 26px;"><p>No fertigation logged against this block's cycles.</p></div>
        @else
        <div class="table-wrap"><table>
            <thead><tr><th>Date</th><th>Nutrient</th><th>Quantity</th><th>Method</th><th>Cost (KES)</th></tr></thead>
            <tbody>
                @foreach($block->fertigationLogs as $log)
                <tr>
                    <td>{{ $log->log_date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $log->nutrient_type }}</td>
                    <td class="mono">{{ number_format($log->quantity, 2) }}</td>
                    <td>{{ $log->method ?? '—' }}</td>
                    <td class="mono">{{ number_format($log->cost, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @endif
    </div>

    {{-- Pest & Spray --}}
    <div class="hub-panel" data-panel="spray">
        @if($block->sprayLogs->isEmpty())
            <div class="empty-state" style="padding: 26px;"><p>No spray applications logged against this block's cycles.</p></div>
        @else
        <div class="table-wrap"><table>
            <thead><tr><th>Date</th><th>Chemical</th><th>Target Pest</th><th>Qty</th><th>PHI (days)</th></tr></thead>
            <tbody>
                @foreach($block->sprayLogs as $log)
                <tr>
                    <td>{{ $log->log_date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $log->chemical_used }}</td>
                    <td>{{ $log->target_pest ?? '—' }}</td>
                    <td class="mono">{{ number_format($log->quantity, 2) }}</td>
                    <td class="mono">{{ $log->pre_harvest_interval_days ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @endif
    </div>

    {{-- Harvest --}}
    <div class="hub-panel" data-panel="harvest">
        @if($block->harvestBatches->isEmpty())
            <div class="empty-state" style="padding: 26px;"><p>No harvest batches from this block yet.</p></div>
        @else
        <div class="table-wrap"><table>
            <thead><tr><th>Date</th><th>Cycle</th><th>Quantity (kg)</th><th>Grade</th><th>Rejects (kg)</th><th></th></tr></thead>
            <tbody>
                @foreach($block->harvestBatches as $batch)
                <tr>
                    <td>{{ $batch->harvest_date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $batch->cropCycle->season_name ?? '—' }}</td>
                    <td class="mono">{{ number_format($batch->quantity_kg, 2) }}</td>
                    <td><span class="badge badge-neutral">{{ $batch->quality_grade ?? '—' }}</span></td>
                    <td class="mono">{{ number_format($batch->rejects_kg, 2) }}</td>
                    <td style="text-align:right;"><a href="{{ route('harvest-batches.show', $batch) }}" class="btn btn-ghost btn-sm">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @endif
    </div>

    {{-- Labour --}}
    <div class="hub-panel" data-panel="labour">
        @if($block->labourAttendances->isEmpty())
            <div class="empty-state" style="padding: 26px;"><p>No labour recorded on this block.</p></div>
        @else
        <div class="table-wrap"><table>
            <thead><tr><th>Date</th><th>Worker</th><th>Task</th><th>Hours</th><th>Cost (KES)</th></tr></thead>
            <tbody>
                @foreach($block->labourAttendances as $att)
                <tr>
                    <td>{{ $att->attendance_date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $att->worker_name }}</td>
                    <td>{{ $att->task ?? '—' }}</td>
                    <td class="mono">{{ number_format($att->hours_worked, 2) }}</td>
                    <td class="mono">{{ number_format($att->cost, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @endif
    </div>

    {{-- Daily Activity --}}
    <div class="hub-panel" data-panel="activity">
        @if($block->dailyActivities->isEmpty())
            <div class="empty-state" style="padding: 26px;"><p>No daily activities recorded for this block.</p></div>
        @else
        <div class="table-wrap"><table>
            <thead><tr><th>Date</th><th>Type</th><th>Description</th></tr></thead>
            <tbody>
                @foreach($block->dailyActivities as $act)
                <tr>
                    <td>{{ $act->activity_date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ ucwords(str_replace('_',' ', $act->activity_type)) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($act->description, 80) ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var container = document.getElementById('hub-tabs');
    if (!container) return;
    var tabs = container.querySelectorAll('.hub-tab');
    var panels = document.querySelectorAll('.hub-panel');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var key = tab.getAttribute('data-panel');
            tabs.forEach(function (t) { t.classList.toggle('active', t === tab); });
            panels.forEach(function (p) { p.classList.toggle('active', p.getAttribute('data-panel') === key); });
        });
    });
});
</script>
@endsection
