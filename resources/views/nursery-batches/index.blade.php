@extends('layouts.app')
@section('title', 'Nursery')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Nursery Batches</h1>
        <p class="page-subtitle">Track seedlings from sowing through to field-ready</p>
    </div>
    <div class="actions">
        <x-help-panel title="Tips — Nursery Batches">
            <p>Sow a batch to start tracking seedlings from <strong>Sown → Growing → Ready → Transplanted</strong>.</p>
            <p><strong>Remaining</strong> quantity drops as seedlings are transplanted out to blocks.</p>
            <p>Once transplanted, a batch feeds into a crop cycle in the field.</p>
            <p><strong>Quick steps:</strong> Click "+ New Batch" → pick the crop and sow date → enter quantity → click "Save", then update its status as it grows.</p>
        </x-help-panel>
        <a href="{{ route('nursery-batches.create') }}" class="btn btn-primary">+ New Batch</a>
    </div>
</div>

<x-search-bar
    :action="route('nursery-batches.index')"
    placeholder="Search by crop name or variety…"
    :search="$search"
    :total="$batches->count()"
    :filters="[
        ['name' => 'status', 'label' => 'All Statuses', 'options' => ['sown' => 'Sown', 'growing' => 'Growing', 'ready' => 'Ready', 'transplanted' => 'Transplanted']],
    ]"
/>

@if($batches->isEmpty())
    <div class="card">
        <div class="empty-state">
            @if($search || request('status'))
                <div class="icon">🔍</div>
                <h3>No nursery batches match your filters</h3>
                <p>Try a different search term or clear your filters.</p>
                <a href="{{ route('nursery-batches.index') }}" class="btn btn-ghost">Clear Filters</a>
            @else
                <div class="icon">🌿</div>
                <h3>No nursery batches</h3>
                <p>Sow a batch to start tracking seedlings before they are transplanted.</p>
                <a href="{{ route('nursery-batches.create') }}" class="btn btn-primary">+ New Batch</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Crop</th>
                        <th>Sow Date</th>
                        <th>Expected Ready</th>
                        <th>Quantity</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batches as $batch)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-primary);">
                            <a href="{{ route('nursery-batches.show', $batch) }}" style="color: var(--accent-hover); text-decoration: none;">{{ $batch->crop->name }} ({{ $batch->crop->variety }})</a>
                        </td>
                        <td>{{ $batch->sow_date->format('M d, Y') }}</td>
                        <td>{{ $batch->expected_ready_date?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ number_format($batch->quantity) }}</td>
                        <td>{{ number_format($batch->remainingQuantity()) }}</td>
                        <td><span class="badge badge-{{ $batch->status }}">{{ ucfirst($batch->status) }}</span></td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('nursery-batches.show', $batch) }}" class="btn btn-ghost btn-sm">View</a>
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
