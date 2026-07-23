@extends('layouts.app')
@section('title', $cropCycleTemplate->crop_name . ' Template')

@section('content')
@php
    $template = $cropCycleTemplate;
    $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'crop_cycles');
    $inUse = $template->cropCycles()->count();
@endphp

<x-crumb-nav />
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $template->crop_name }}{{ $template->variety ? ' — '.$template->variety : '' }}</h1>
        <p class="page-subtitle">
            {{ $template->total_cycle_days }}-day cycle ·
            {{ $template->stages->count() }} stage(s) ·
            {{ $template->schedulePoints->count() }} schedule point(s)
            @if($inUse) · running on {{ $inUse }} cycle(s) @endif
        </p>
    </div>
    <div class="actions">
        @if($canWrite)
            <a href="{{ route('crop-cycle-templates.edit', $template) }}" class="btn btn-secondary">Edit</a>
            <form action="{{ route('crop-cycle-templates.destroy', $template) }}" method="POST"
                  data-confirm="Delete this template?">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        @endif
    </div>
</div>

@if($template->description)
    <div class="card"><p>{{ $template->description }}</p></div>
@endif

@if($template->schedulePoints->isEmpty())
    <div class="alert alert-warning">
        This template has no schedule points, so cycles running it will never raise a reminder. Add the spray and input days below.
    </div>
@endif

{{-- Growth stages --}}
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Growth Stages</h2>
        <p class="card-subtitle">Phases of the cycle, as days from planting.</p>
    </div>

    @if($template->stages->isEmpty())
        <div class="empty-state"><p>No stages defined yet.</p></div>
    @else
        <table class="table">
            <thead>
                <tr><th>#</th><th>Stage</th><th>Days</th><th class="num">Schedule points</th>@if($canWrite)<th></th>@endif</tr>
            </thead>
            <tbody>
                @foreach($template->stages as $stage)
                    <tr>
                        <td>{{ $stage->sort_order }}</td>
                        <td><strong>{{ $stage->stage_name }}</strong></td>
                        <td>{{ $stage->dayRange() }}</td>
                        <td class="num">{{ $template->schedulePoints->where('crop_cycle_stage_id', $stage->id)->count() }}</td>
                        @if($canWrite)
                            <td class="num">
                                <form action="{{ route('crop-cycle-templates.stages.destroy', [$template, $stage]) }}" method="POST"
                                      data-confirm="Remove the {{ $stage->stage_name }} stage?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-ghost">Remove</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($canWrite)
        <form action="{{ route('crop-cycle-templates.stages.store', $template) }}" method="POST" style="margin-top:16px;">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="stage_name">Stage Name *</label>
                    <input type="text" id="stage_name" name="stage_name" class="form-input" required placeholder="e.g. Fruiting">
                </div>
                <div class="form-group">
                    <label class="form-label" for="start_day_offset">Start Day *</label>
                    <input type="number" id="start_day_offset" name="start_day_offset" class="form-input" required min="0" max="{{ $template->total_cycle_days }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="end_day_offset">End Day *</label>
                    <input type="number" id="end_day_offset" name="end_day_offset" class="form-input" required min="0" max="{{ $template->total_cycle_days }}">
                </div>
            </div>
            <button type="submit" class="btn btn-secondary">Add Stage</button>
        </form>
    @endif
</div>

{{-- Schedule points --}}
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Spray &amp; Input Schedule</h2>
        <p class="card-subtitle">What to apply and when. Each point becomes a dated task on every active cycle running this template.</p>
    </div>

    @if($template->schedulePoints->isEmpty())
        <div class="empty-state"><p>No schedule points yet.</p></div>
    @else
        <table class="table">
            <thead>
                <tr><th>Day</th><th>Activity</th><th>Product</th><th>Dosage</th><th>Purpose</th><th>Stage</th><th>PHI</th>@if($canWrite)<th></th>@endif</tr>
            </thead>
            <tbody>
                @foreach($template->schedulePoints as $point)
                    <tr>
                        <td><strong>Day {{ $point->day_offset }}</strong></td>
                        <td>{{ $point->activityLabel() }}</td>
                        <td>{{ $point->product_name ?: '—' }}</td>
                        <td>{{ $point->dosage ?: '—' }}</td>
                        <td>{{ $point->purpose ?: '—' }}</td>
                        <td>{{ $point->stage?->stage_name ?: '—' }}</td>
                        <td>{{ $point->pre_harvest_interval_days !== null ? $point->pre_harvest_interval_days.'d' : '—' }}</td>
                        @if($canWrite)
                            <td class="num">
                                <form action="{{ route('crop-cycle-templates.points.destroy', [$template, $point]) }}" method="POST"
                                      data-confirm="Remove the day {{ $point->day_offset }} point?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-ghost">Remove</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($canWrite)
        <form action="{{ route('crop-cycle-templates.points.store', $template) }}" method="POST" style="margin-top:16px;">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="day_offset">Day *</label>
                    <input type="number" id="day_offset" name="day_offset" class="form-input" required min="0" max="{{ $template->total_cycle_days }}" placeholder="60">
                </div>
                <div class="form-group">
                    <label class="form-label" for="activity_type">Activity *</label>
                    <select id="activity_type" name="activity_type" class="form-select" required>
                        @foreach($activityTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="product_name">Product</label>
                    <input type="text" id="product_name" name="product_name" class="form-input" placeholder="e.g. Mancozeb fungicide">
                </div>
                <div class="form-group">
                    <label class="form-label" for="dosage">Dosage</label>
                    <input type="text" id="dosage" name="dosage" class="form-input" placeholder="e.g. 2kg/ha">
                </div>
                <div class="form-group">
                    <label class="form-label" for="purpose">Purpose</label>
                    <input type="text" id="purpose" name="purpose" class="form-input" placeholder="e.g. blight prevention">
                </div>
                <div class="form-group">
                    <label class="form-label" for="crop_cycle_stage_id">Stage</label>
                    <select id="crop_cycle_stage_id" name="crop_cycle_stage_id" class="form-select">
                        <option value="">Not tied to a stage</option>
                        @foreach($template->stages as $stage)
                            <option value="{{ $stage->id }}">{{ $stage->stage_name }} ({{ $stage->dayRange() }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="pre_harvest_interval_days">Pre-harvest interval (days)</label>
                    <input type="number" id="pre_harvest_interval_days" name="pre_harvest_interval_days" class="form-input" min="0" placeholder="e.g. 7">
                    <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">How long after this spray harvest must wait.</p>
                </div>
            </div>
            <button type="submit" class="btn btn-secondary">Add Schedule Point</button>
        </form>
    @endif
</div>
@endsection
