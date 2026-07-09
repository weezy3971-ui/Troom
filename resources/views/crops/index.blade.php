@extends('layouts.app')
@section('title', 'Crops')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Crop Catalogue</h1>
        <p class="page-subtitle">Manage crop varieties and their expected yields</p>
    </div>
    <a href="{{ route('crops.create') }}" class="btn btn-primary">+ Add Crop</a>
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
                <a href="{{ route('crops.create') }}" class="btn btn-primary">+ Add Crop</a>
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
                                <a href="{{ route('crops.edit', $crop) }}" class="btn btn-ghost btn-sm">Edit</a>
                                <form action="{{ route('crops.destroy', $crop) }}" method="POST" onsubmit="return confirm('Delete this crop?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
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
