@extends('layouts.app')
@section('title', $crop->name)

@section('content')
@php $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'master_data'); @endphp
<div class="breadcrumbs">
    <a href="{{ route('crops.index') }}">Crops</a> <span>/</span> <span>{{ $crop->name }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $crop->name }}</h1>
        <p class="page-subtitle">{{ $crop->variety ?? 'No variety specified' }} — {{ $crop->crop_type }}</p>
    </div>
    @if($canWrite)
    <div class="actions">
        <a href="{{ route('crops.edit', $crop) }}" class="btn btn-secondary">Edit</a>
    </div>
    @endif
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Crop Type</div>
        <div class="detail-value">{{ $crop->crop_type }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Variety</div>
        <div class="detail-value">{{ $crop->variety ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Days to Maturity</div>
        <div class="detail-value">{{ $crop->days_to_maturity ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Expected Yield / Acre</div>
        <div class="detail-value">{{ $crop->expected_yield_per_acre ? number_format($crop->expected_yield_per_acre) . ' kg' : '—' }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Crop Cycles using this Crop</h3>
    </div>
    @if($crop->cropCycles->isEmpty())
        <div class="empty-state" style="padding: 30px;"><p>No crop cycles using this crop yet.</p></div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Block</th><th>Farm</th><th>Season</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($crop->cropCycles as $cycle)
                    <tr>
                        <td>{{ $cycle->block->name }}</td>
                        <td>{{ $cycle->block->farm->name }}</td>
                        <td>{{ $cycle->season_name }}</td>
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
