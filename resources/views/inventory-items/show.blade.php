@extends('layouts.app')
@section('title', $inventoryItem->name)

@section('content')
<x-crumb-nav />

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $inventoryItem->name }}</h1>
        <p class="page-subtitle">{{ ucfirst($inventoryItem->category) }} · {{ $inventoryItem->farm->name ?? 'Central store' }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('inventory-items.edit', $inventoryItem) }}" class="btn btn-secondary">Edit</a>
        <form action="{{ route('inventory-items.destroy', $inventoryItem) }}" method="POST" data-confirm="Delete this item?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

@if($inventoryItem->isLowStock())
    <div class="alert alert-warning">⚠ Low stock — on hand ({{ number_format($inventoryItem->currentStock(), 2) }}) is below the reorder level ({{ number_format($inventoryItem->reorder_level, 2) }}).</div>
@endif

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">On Hand</div>
        <div class="detail-value">{{ number_format($inventoryItem->currentStock(), 2) }} {{ $inventoryItem->unit }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Reorder Level</div>
        <div class="detail-value">{{ number_format($inventoryItem->reorder_level, 2) }} {{ $inventoryItem->unit }}</div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Category</div>
        <div class="detail-value">{{ ucfirst($inventoryItem->category) }}</div>
    </div>
</div>

<div class="cols-2" style="display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 20px; align-items: start;">
    {{-- Record movement --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Record Movement</h3></div>
        <form action="{{ route('inventory-items.transactions.store', $inventoryItem) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="type">Type *</label>
                <select id="type" name="type" class="form-select" required>
                    <option value="receipt">Receipt (in)</option>
                    <option value="issue">Issue (out)</option>
                    <option value="adjustment">Adjustment</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity">Quantity *</label>
                <input type="number" step="0.01" id="quantity" name="quantity" class="form-input" min="0.01" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="transaction_date">Date *</label>
                <input type="date" id="transaction_date" name="transaction_date" value="{{ now()->toDateString() }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="cost">Cost (KES)</label>
                <input type="number" step="0.01" id="cost" name="cost" class="form-input" min="0">
            </div>
            <div class="form-group">
                <label class="form-label" for="crop_cycle_id">Crop Cycle (issues)</label>
                <select id="crop_cycle_id" name="crop_cycle_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($cropCycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->season_name }} — {{ $cycle->block->name ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="reference">Reference</label>
                <input type="text" id="reference" name="reference" class="form-input">
            </div>
            <button type="submit" class="btn btn-primary">Record</button>
        </form>
    </div>

    {{-- Movement history --}}
    <div class="card" style="padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Cost</th>
                        <th>Crop Cycle</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventoryItem->transactions->sortByDesc('transaction_date') as $txn)
                    <tr>
                        <td>{{ $txn->transaction_date->format('M d, Y') }}</td>
                        <td>
                            @if($txn->type === 'issue')
                                <span class="badge badge-down">Issue</span>
                            @elseif($txn->type === 'receipt')
                                <span class="badge badge-active">Receipt</span>
                            @else
                                <span class="badge badge-neutral">Adjustment</span>
                            @endif
                        </td>
                        <td class="mono">{{ $txn->signedQuantity() > 0 ? '+' : '' }}{{ number_format($txn->signedQuantity(), 2) }}</td>
                        <td class="mono">{{ $txn->cost ? number_format($txn->cost, 2) : '—' }}</td>
                        <td>{{ $txn->cropCycle->season_name ?? '—' }}</td>
                        <td>{{ $txn->reference ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 24px;">No movements recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
