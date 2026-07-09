@extends('layouts.app')
@section('title', 'Assets')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Assets & Equipment</h1>
        <p class="page-subtitle">Pumps, vehicles, and farm equipment</p>
    </div>
    <a href="{{ route('assets.create') }}" class="btn btn-primary">+ Register Asset</a>
</div>

<x-search-bar
    :action="route('assets.index')"
    placeholder="Search assets by name or farm…"
    :search="$search"
    :total="$assets->count()"
    :filters="[
        ['name' => 'type', 'label' => 'All Types', 'options' => ['pump' => 'Pump', 'vehicle' => 'Vehicle', 'equipment' => 'Equipment']],
        ['name' => 'status', 'label' => 'All Statuses', 'options' => ['operational' => 'Operational', 'maintenance' => 'Maintenance', 'down' => 'Down']],
    ]"
/>

@if($assets->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search || request('type') || request('status'))
                <div class="icon">🔍</div>
                <h3>No assets match your filters</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('assets.index') }}" class="btn btn-ghost">Clear Filters</a>
            @else
                <div class="icon">🚜</div>
                <h3>No assets registered</h3>
                <p>Register pumps, vehicles, and equipment to track their usage and maintenance.</p>
                <a href="{{ route('assets.create') }}" class="btn btn-primary">+ Register Asset</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Farm</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Purchase Date</th>
                        <th>Hours / Mileage</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assets as $asset)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('assets.show', $asset) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $asset->name }}</a>
                        </td>
                        <td>{{ $asset->farm->name }}</td>
                        <td><span class="badge badge-{{ $asset->type }}">{{ ucfirst($asset->type) }}</span></td>
                        <td><span class="badge badge-{{ $asset->status }}">{{ ucfirst($asset->status) }}</span></td>
                        <td>{{ $asset->purchase_date?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ number_format($asset->current_hours) }}h / {{ number_format($asset->current_mileage) }}km</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('assets.edit', $asset) }}" class="btn btn-ghost btn-sm">Edit</a>
                                <form action="{{ route('assets.destroy', $asset) }}" method="POST" onsubmit="return confirm('Decommission this asset?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
