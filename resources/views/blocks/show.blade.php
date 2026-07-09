@extends('layouts.app')
@section('title', $block->name)

@section('content')
<div class="breadcrumbs">
    <a href="{{ route('blocks.index') }}">Blocks</a> <span>/</span> <span>{{ $block->name }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $block->name }}</h1>
        <p class="page-subtitle">{{ $block->farm->name }} — {{ $block->soil_type ?? 'Soil type not set' }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('blocks.edit', $block) }}" class="btn btn-secondary">Edit</a>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Farm</div>
        <div class="detail-value"><a href="{{ route('farms.show', $block->farm) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $block->farm->name }}</a></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Size</div>
        <div class="detail-value">{{ number_format($block->size_acres, 1) }} acres</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Soil Type</div>
        <div class="detail-value">{{ $block->soil_type ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Total Crop Cycles</div>
        <div class="detail-value">{{ $block->cropCycles->count() }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Crop Cycles on this Block</h3>
        <a href="{{ route('crop-cycles.create') }}" class="btn btn-primary btn-sm">+ New Cycle</a>
    </div>
    @if($block->cropCycles->isEmpty())
        <div class="empty-state" style="padding: 30px;">
            <p>No crop cycles for this block yet.</p>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Season</th><th>Crop</th><th>Planting Date</th><th>Expected Harvest</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($block->cropCycles as $cycle)
                    <tr>
                        <td>{{ $cycle->season_name }}</td>
                        <td>{{ $cycle->crop->name }}</td>
                        <td>{{ $cycle->planting_date?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ $cycle->expected_harvest_date?->format('M d, Y') ?? '—' }}</td>
                        <td><span class="badge badge-{{ $cycle->status }}">{{ ucfirst($cycle->status) }}</span></td>
                        <td><a href="{{ route('crop-cycles.show', $cycle) }}" class="btn btn-ghost btn-sm">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
