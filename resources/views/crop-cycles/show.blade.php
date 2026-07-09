@extends('layouts.app')
@section('title', $cropCycle->season_name)

@section('content')
@php $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'crop_cycles'); @endphp
<div class="breadcrumbs">
    <a href="{{ route('crop-cycles.index') }}">Crop Cycles</a> <span>/</span> <span>{{ $cropCycle->season_name }}</span>
</div>

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
            <form action="{{ route('crop-cycles.cancel', $cropCycle) }}" method="POST" onsubmit="return confirm('Cancel this crop cycle?')">
                @csrf
                <button type="submit" class="btn btn-danger">Cancel Cycle</button>
            </form>
        @endif
        <a href="{{ route('crop-cycles.edit', $cropCycle) }}" class="btn btn-secondary">Edit</a>
    </div>
    @endif
</div>

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
    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
        <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 16px;">{{ $cropCycle->seasonalBudget ? 'Update Budget' : 'Set Budget (required before activation)' }}</h4>
        <form action="{{ route('crop-cycles.budget', $cropCycle) }}" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="labour_budget">Labour Budget (KES)</label>
                    <input type="number" id="labour_budget" name="labour_budget" value="{{ old('labour_budget', $cropCycle->seasonalBudget?->labour_budget ?? 0) }}" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="input_budget">Input Budget (KES)</label>
                    <input type="number" id="input_budget" name="input_budget" value="{{ old('input_budget', $cropCycle->seasonalBudget?->input_budget ?? 0) }}" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="irrigation_budget">Irrigation Budget (KES)</label>
                    <input type="number" id="irrigation_budget" name="irrigation_budget" value="{{ old('irrigation_budget', $cropCycle->seasonalBudget?->irrigation_budget ?? 0) }}" class="form-input" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="overhead_budget">Overhead Budget (KES)</label>
                    <input type="number" id="overhead_budget" name="overhead_budget" value="{{ old('overhead_budget', $cropCycle->seasonalBudget?->overhead_budget ?? 0) }}" class="form-input" step="0.01" min="0" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Budget</button>
        </form>
    </div>
    @endif
</div>
@endsection
