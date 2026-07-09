@extends('layouts.app')
@section('title', 'Farms')

@section('content')
@php $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'master_data'); @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">Farms</h1>
        <p class="page-subtitle">Manage your farm locations and sizes</p>
    </div>
    @if($canWrite)<a href="{{ route('farms.create') }}" class="btn btn-primary">+ Add Farm</a>@endif
</div>

<x-search-bar
    :action="route('farms.index')"
    placeholder="Search farms by name or location…"
    :search="$search"
    :total="$farms->count()"
/>

@if($farms->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No farms match "{{ $search }}"</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('farms.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">🌾</div>
                <h3>No farms registered yet</h3>
                <p>Start by adding your first farm to the system.</p>
                @if($canWrite)<a href="{{ route('farms.create') }}" class="btn btn-primary">+ Add Farm</a>@endif
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
                        <th>Location</th>
                        <th>Size (Acres)</th>
                        <th>Blocks</th>
                        <th>Assets</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($farms as $farm)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('farms.show', $farm) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $farm->name }}</a>
                        </td>
                        <td>{{ $farm->location }}</td>
                        <td>{{ number_format($farm->size_acres, 1) }}</td>
                        <td>{{ $farm->blocks_count }}</td>
                        <td>{{ $farm->assets_count }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('farms.show', $farm) }}" class="btn btn-ghost btn-sm">View</a>
                                @if($canWrite)
                                <a href="{{ route('farms.edit', $farm) }}" class="btn btn-ghost btn-sm">Edit</a>
                                <form action="{{ route('farms.destroy', $farm) }}" method="POST" onsubmit="return confirm('Delete this farm?')">
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
