@extends('layouts.app')
@section('title', 'Inventory')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Inventory &amp; Stores</h1>
        <p class="page-subtitle">Stock of inputs and spares across farms</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Inventory">
            <p><strong>Register items</strong> you keep in stock — fertilisers, chemicals, packaging, spare parts.</p>
            <p><strong>Pre-harvest vs Post-harvest:</strong> use the stage filter to separate field inputs from packing materials.</p>
            <p><strong>Reorder level</strong> triggers a "Low stock" badge when on-hand drops below it.</p>
            <p>Procurement receipts automatically increase stock; usage logs decrease it.</p>
            <p><strong>Quick steps:</strong> Click "+ New Item" → enter name, stage, and reorder level → click "Save".</p>
        </x-help-panel>
        <a href="{{ route('inventory-items.create') }}" class="btn btn-primary">+ New Item</a>
    </div>
</div>

<x-search-bar
    :action="route('inventory-items.index')"
    placeholder="Search by name or category…"
    :search="$search"
    :total="$items->count()"
/>

{{-- Pre-harvest vs post-harvest store filter --}}
<div style="display:flex; gap:8px; margin: 4px 0 16px; flex-wrap:wrap;">
    <a href="{{ route('inventory-items.index', array_filter(['search' => $search])) }}" class="btn btn-sm {{ !$stage ? 'btn-primary' : 'btn-ghost' }}">All</a>
    @foreach(\App\Models\InventoryItem::STAGES as $value => $label)
        <a href="{{ route('inventory-items.index', array_filter(['search' => $search, 'stage' => $value])) }}" class="btn btn-sm {{ $stage === $value ? 'btn-primary' : 'btn-ghost' }}">{{ $label }}</a>
    @endforeach
</div>

@if($items->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search)
                <div class="icon">🔍</div>
                <h3>No items match "{{ $search }}"</h3>
                <a href="{{ route('inventory-items.index') }}" class="btn btn-ghost">Clear Search</a>
            @else
                <div class="icon">📦</div>
                <h3>No inventory items</h3>
                <p>Register inputs and spares to track stock and reorder levels.</p>
                <a href="{{ route('inventory-items.create') }}" class="btn btn-primary">+ New Item</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Stage</th>
                        <th>Farm</th>
                        <th>On Hand</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('inventory-items.show', $item) }}" style="color: var(--olive); text-decoration: none;">{{ $item->name }}</a>
                        </td>
                        <td>{{ ucfirst($item->category) }}</td>
                        <td><span class="badge {{ $item->stage === 'post_harvest_packaging' ? 'badge-planned' : ($item->stage === 'pre_harvest_input' ? 'badge-active' : 'badge-secondary') }}">{{ $item->stageLabel() }}</span></td>
                        <td>{{ $item->farm->name ?? '—' }}</td>
                        <td class="mono">{{ number_format($item->currentStock(), 2) }} {{ $item->unit }}</td>
                        <td class="mono">{{ number_format($item->reorder_level, 2) }}</td>
                        <td>
                            @if($item->isLowStock())
                                <span class="badge badge-down">Low stock</span>
                            @else
                                <span class="badge badge-active">OK</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('inventory-items.show', $item) }}" class="btn btn-ghost btn-sm">View</a>
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
