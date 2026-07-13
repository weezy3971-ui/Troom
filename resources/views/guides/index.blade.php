@extends('layouts.app')
@section('title', 'Guides')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Ride Guides</h1>
        <p class="page-subtitle">Staff who lead horse rides</p>
    </div>
    <div class="actions">
        <a href="{{ route('horses.index') }}" class="btn btn-ghost">Horses</a>
        <a href="{{ route('rides.index') }}" class="btn btn-ghost">Rides</a>
        <a href="{{ route('guides.create') }}" class="btn btn-primary">+ Add Guide</a>
    </div>
</div>

<x-search-bar
    :action="route('guides.index')"
    placeholder="Search by name or phone…"
    :search="$search"
    :total="$guides->count()"
/>

@if($guides->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">🧑‍🌾</div>
            <h3>No guides yet</h3>
            <p>Add guides so they can be assigned to rides.</p>
            <a href="{{ route('guides.create') }}" class="btn btn-primary">+ Add Guide</a>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>Phone</th><th>Rides</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($guides as $guide)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">{{ $guide->name }}</td>
                        <td>{{ $guide->phone ?? '—' }}</td>
                        <td>{{ $guide->rides_count }}</td>
                        <td><span class="badge {{ $guide->is_active ? 'badge-active' : 'badge-completed' }}">{{ $guide->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('guides.edit', $guide) }}" class="btn btn-ghost btn-sm">Edit</a>
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
