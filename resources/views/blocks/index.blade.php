@extends('layouts.app')
@section('title', 'Blocks')

@section('content')
@php $canWrite = \App\Support\ModuleAccess::allows(auth()->user(), 'master_data'); @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">Blocks</h1>
        <p class="page-subtitle">Farm subdivisions where crops are planted</p>
    </div>
    @if($canWrite)<a href="{{ route('blocks.create') }}" class="btn btn-primary">+ Add Block</a>@endif
</div>

<x-search-bar
    :action="route('blocks.index')"
    placeholder="Search blocks by name, farm, or soil type…"
    :search="$search"
    :total="$blocks->count()"
/>

@if($blocks->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No blocks match "{{ $search }}"</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('blocks.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">🗺️</div>
                <h3>No blocks registered yet</h3>
                <p>Blocks are subdivisions of farms where individual crops are grown.</p>
                @if($canWrite)<a href="{{ route('blocks.create') }}" class="btn btn-primary">+ Add Block</a>@endif
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
                        <th>Size (Acres)</th>
                        <th>Soil Type</th>
                        <th>Crop Cycles</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($blocks as $block)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('blocks.show', $block) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $block->name }}</a>
                        </td>
                        <td><a href="{{ route('farms.show', $block->farm) }}" style="color: var(--text-secondary); text-decoration: none;">{{ $block->farm->name }}</a></td>
                        <td>{{ number_format($block->size_acres, 1) }}</td>
                        <td>{{ $block->soil_type ?? '—' }}</td>
                        <td>{{ $block->crop_cycles_count }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('blocks.show', $block) }}" class="btn btn-ghost btn-sm">View</a>
                                @if($canWrite)
                                <a href="{{ route('blocks.edit', $block) }}" class="btn btn-ghost btn-sm">Edit</a>
                                <form action="{{ route('blocks.destroy', $block) }}" method="POST" onsubmit="return confirm('Delete this block?')">
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
