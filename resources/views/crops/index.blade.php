@extends('layouts.app')
@section('title', 'Crops')

@section('content')
@php $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'master_data'); @endphp
<x-crop-tabs />

<div class="page-header">
    <div>
        <h1 class="page-title">Crop Catalogue</h1>
        <p class="page-subtitle">Manage crop varieties and their expected yields</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Crops">
            <p><strong>Add a crop variety</strong> for each distinct thing you grow — e.g. "French Beans — Serengeti".</p>
            <p><strong>Days to Maturity</strong> and <strong>Yield/Acre</strong> feed into crop-cycle projections and planting plans.</p>
            <p><strong>Crop Programs</strong> (stage schedules) link to a crop, so set up the crop first, then define the program.</p>
            <p><strong>Quick steps:</strong> Click "+ Add Crop" → enter name, variety, days to maturity, and yield/acre → click "Save".</p>
        </x-help-panel>
        @if($canWrite)<a href="{{ route('crops.create') }}" class="btn btn-primary">+ Add Crop</a>@endif
    </div>
</div>

<x-search-bar
    :action="route('crops.index')"
    placeholder="Search by crop name, variety, or type…"
    :search="$search"
    :total="$crops->count()"
/>

@if($crops->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No crops match "{{ $search }}"</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('crops.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">🌱</div>
                <h3>No crops in catalogue</h3>
                <p>Add your crop varieties to start planning crop cycles.</p>
                @if($canWrite)<a href="{{ route('crops.create') }}" class="btn btn-primary">+ Add Crop</a>@endif
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
                        <th>Variety</th>
                        <th>Type</th>
                        <th>Days to Maturity</th>
                        <th>Yield/Acre (kg)</th>
                        <th>Cycles</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($crops as $crop)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('crops.show', $crop) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $crop->name }}</a>
                        </td>
                        <td>{{ $crop->variety ?? '—' }}</td>
                        <td>{{ $crop->crop_type }}</td>
                        <td>{{ $crop->days_to_maturity ?? '—' }}</td>
                        <td>{{ $crop->expected_yield_per_acre ? number_format($crop->expected_yield_per_acre) : '—' }}</td>
                        <td>{{ $crop->crop_cycles_count }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('crops.show', $crop) }}" class="btn btn-ghost btn-sm">View</a>
                                @if($canWrite)
                                <a href="{{ route('crops.edit', $crop) }}" class="btn btn-ghost btn-sm">Edit</a>
                                <form action="{{ route('crops.destroy', $crop) }}" method="POST" data-confirm="Delete this crop?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                                @endif
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
