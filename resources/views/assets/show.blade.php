@extends('layouts.app')
@section('title', $asset->name)

@section('content')
@php $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'master_data'); @endphp
<div class="breadcrumbs">
    <a href="{{ route('assets.index') }}">Assets</a> <span>/</span> <span>{{ $asset->name }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $asset->name }}</h1>
        <p class="page-subtitle">{{ $asset->farm->name }} — <span class="badge badge-{{ $asset->type }}">{{ ucfirst($asset->type) }}</span></p>
    </div>
    @if($canWrite)
    <div class="actions">
        <a href="{{ route('assets.edit', $asset) }}" class="btn btn-secondary">Edit</a>
    </div>
    @endif
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Status</div>
        <div class="detail-value"><span class="badge badge-{{ $asset->status }}">{{ ucfirst($asset->status) }}</span></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Farm</div>
        <div class="detail-value"><a href="{{ route('farms.show', $asset->farm) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $asset->farm->name }}</a></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Purchase Date</div>
        <div class="detail-value">{{ $asset->purchase_date?->format('M d, Y') ?? '—' }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Hours / Mileage</div>
        <div class="detail-value">{{ number_format($asset->current_hours) }}h / {{ number_format($asset->current_mileage) }}km</div>
    </div>
</div>

{{-- Status History Audit Trail --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Status History (Audit Trail)</h3>
    </div>
    @if($asset->statusHistories->isEmpty())
        <div class="empty-state" style="padding: 30px;">
            <p>No status changes recorded yet.</p>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Date</th><th>From</th><th>To</th><th>Changed By</th></tr>
                </thead>
                <tbody>
                    @foreach($asset->statusHistories->sortByDesc('changed_at') as $history)
                    <tr>
                        <td>{{ $history->changed_at->format('M d, Y H:i') }}</td>
                        <td><span class="badge badge-{{ $history->old_status }}">{{ ucfirst($history->old_status) }}</span></td>
                        <td><span class="badge badge-{{ $history->new_status }}">{{ ucfirst($history->new_status) }}</span></td>
                        <td>{{ $history->changedByUser?->name ?? 'System' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
