@extends('layouts.app')
@section('title', 'Outgrowers')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Outgrowers</h1>
        <p class="page-subtitle">External growers used to top up an order when in-house lots fall short</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Outgrowers">
            <p><strong>Outgrowers</strong> are external suppliers. When your farm can't fill an order, you source from them.</p>
            <p><strong>Reliability rating</strong> (1–5 stars) helps decide who to call first.</p>
            <p>When creating a sales order line, you can assign it to an outgrower instead of an in-house lot.</p>
            <p><strong>Quick steps:</strong> Click "+ Add Outgrower" → enter name, contact, and specialization → set a reliability rating → click "Save".</p>
        </x-help-panel>
        <a href="{{ route('outgrowers.create') }}" class="btn btn-primary">+ Add Outgrower</a>
    </div>
</div>

<x-search-bar
    :action="route('outgrowers.index')"
    placeholder="Search by name or location…"
    :search="$search"
    :total="$outgrowers->count()"
    :filters="[
        ['name' => 'status', 'label' => 'All Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
    ]"
/>

@if($outgrowers->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="icon">🧑‍🌾</div>
            <h3>No outgrowers yet</h3>
            <p>Add external growers so an order can be topped up when there's no in-house lot.</p>
            <a href="{{ route('outgrowers.create') }}" class="btn btn-primary">+ Add Outgrower</a>
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>Phone</th><th>Location</th><th>Specialization</th><th>Order Lines</th><th>Rating</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($outgrowers as $outgrower)
                    <tr>
                        <td style="font-weight: 600;"><a href="{{ route('outgrowers.show', $outgrower) }}" style="color: var(--olive); text-decoration: none;">{{ $outgrower->name }}</a></td>
                        <td>{{ $outgrower->phone ?? '—' }}</td>
                        <td>{{ $outgrower->location ?? '—' }}</td>
                        <td>{{ $outgrower->specialization ?? '—' }}</td>
                        <td>{{ $outgrower->sales_order_lines_count }}</td>
                        <td>@if($outgrower->reliability_rating)<span style="color: var(--gold); letter-spacing: 1px;">{{ str_repeat('★', $outgrower->reliability_rating) }}{{ str_repeat('☆', 5 - $outgrower->reliability_rating) }}</span>@else —@endif</td>
                        <td>
                            <span class="badge {{ $outgrower->is_active ? 'badge-active' : 'badge-neutral' }}">{{ $outgrower->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('outgrowers.edit', $outgrower) }}" class="btn btn-ghost btn-sm">Edit</a>
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
