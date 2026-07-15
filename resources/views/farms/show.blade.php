@extends('layouts.app')
@section('title', $farm->name)

@section('content')
@php $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'master_data'); @endphp
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $farm->name }}</h1>
        <p class="page-subtitle">{{ $farm->location }}</p>
    </div>
    @if($canWrite)
    <div class="actions">
        <a href="{{ route('farms.edit', $farm) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('farms.destroy', $farm) }}" method="POST" data-confirm="Delete this farm and all its blocks?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
    @endif
</div>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Size</div>
        <div class="detail-value">{{ number_format($farm->size_acres, 1) }} acres</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Blocks</div>
        <div class="detail-value">{{ $farm->blocks->count() }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Assets</div>
        <div class="detail-value">{{ $farm->assets->count() }}</div>
    </div>
    @if($farm->latitude && $farm->longitude)
    <div class="detail-item">
        <div class="detail-label">Coordinates</div>
        <div class="detail-value" style="font-size: 13px;">{{ $farm->latitude }}, {{ $farm->longitude }}</div>
    </div>
    @endif
</div>

{{-- Blocks --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3 class="card-title">Blocks in this Farm</h3>
        @if($canWrite)<a href="{{ route('blocks.create') }}" class="btn btn-primary btn-sm">+ Add Block</a>@endif
    </div>
    @if($farm->blocks->isEmpty())
        <div class="empty-state" style="padding: 30px;">
            <p>No blocks in this farm yet.</p>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>Size (Acres)</th><th>Soil Type</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($farm->blocks as $block)
                    <tr>
                        <td><a href="{{ route('blocks.show', $block) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $block->name }}</a></td>
                        <td>{{ number_format($block->size_acres, 1) }}</td>
                        <td>{{ $block->soil_type ?? '—' }}</td>
                        <td><a href="{{ route('blocks.show', $block) }}" class="btn btn-ghost btn-sm">View</a>@if($canWrite)<a href="{{ route('blocks.edit', $block) }}" class="btn btn-ghost btn-sm">Edit</a>@endif</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Assets --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Assets in this Farm</h3>
        @if($canWrite)<a href="{{ route('assets.create') }}" class="btn btn-primary btn-sm">+ Add Asset</a>@endif
    </div>
    @if($farm->assets->isEmpty())
        <div class="empty-state" style="padding: 30px;">
            <p>No assets registered for this farm yet.</p>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>Type</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($farm->assets as $asset)
                    <tr>
                        <td><a href="{{ route('assets.show', $asset) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $asset->name }}</a></td>
                        <td><span class="badge badge-{{ $asset->type }}">{{ ucfirst($asset->type) }}</span></td>
                        <td><span class="badge badge-{{ $asset->status }}">{{ ucfirst($asset->status) }}</span></td>
                        <td><a href="{{ route('assets.show', $asset) }}" class="btn btn-ghost btn-sm">View</a>@if($canWrite)<a href="{{ route('assets.edit', $asset) }}" class="btn btn-ghost btn-sm">Edit</a>@endif</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
