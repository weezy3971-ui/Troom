@extends('layouts.app')
@section('title', 'Procurement Request #' . $procurementRequest->id)

@section('content')
<x-crumb-nav />
<div class="page-header">
    <div>
        <h1 class="page-title">Procurement Request #{{ $procurementRequest->id }}</h1>
        <p class="page-subtitle">
            <span class="badge {{ $procurementRequest->status === 'received' ? 'badge-completed' : ($procurementRequest->status === 'ordered' ? 'badge-planned' : 'badge-active') }}">{{ ucfirst($procurementRequest->status) }}</span>
            @if($procurementRequest->cropCycle) · {{ $procurementRequest->cropCycle->season_name }} @endif
            @if($procurementRequest->needed_by) · needed by {{ $procurementRequest->needed_by->format('M d, Y') }} @endif
        </p>
    </div>
    <div class="actions">
        @if($procurementRequest->status === 'requested')
            <form action="{{ route('procurement-requests.order', $procurementRequest) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary" @if($procurementRequest->lines->isEmpty()) disabled title="Add items first" @endif>Mark Ordered</button>
            </form>
        @endif
        @unless($procurementRequest->isReceived())
            <form action="{{ route('procurement-requests.receive', $procurementRequest) }}" method="POST" data-confirm="Mark received? Linked items will be added to inventory.">
                @csrf
                <button type="submit" class="btn btn-primary" @if($procurementRequest->lines->isEmpty()) disabled title="Add items first" @endif>Mark Received</button>
            </form>
        @endunless
        <form action="{{ route('procurement-requests.destroy', $procurementRequest) }}" method="POST" data-confirm="Delete this request?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

@if($procurementRequest->notes)
    <div class="alert alert-info">{{ $procurementRequest->notes }}</div>
@endif

<div class="card" style="padding: 0; margin-bottom: 24px;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display:flex; align-items:center;">
        <h3 style="margin: 0;">Items</h3>
        <span style="margin-left:auto; font-weight:600;">Est. total: KES {{ number_format($procurementRequest->estimatedTotal()) }}</span>
    </div>
    @if($procurementRequest->lines->isEmpty())
        <div style="padding: 20px;"><p class="page-subtitle" style="margin:0;">No items yet.</p></div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Item</th><th>Inventory link</th><th>Qty</th><th>Unit</th><th>Est. Cost</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($procurementRequest->lines as $line)
                    <tr>
                        <td style="font-weight:600; color: var(--text-primary);">{{ $line->item_name }}</td>
                        <td>{{ $line->inventoryItem?->name ?? '— (not linked)' }}</td>
                        <td>{{ number_format($line->quantity, 2) }}</td>
                        <td>{{ $line->unit ?? '—' }}</td>
                        <td>{{ $line->estimated_cost !== null ? 'KES ' . number_format($line->estimated_cost) : '—' }}</td>
                        <td>
                            @unless($procurementRequest->isReceived())
                            <form action="{{ route('procurement-requests.lines.destroy', [$procurementRequest, $line]) }}" method="POST" data-confirm="Remove this item?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
                            </form>
                            @endunless
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@unless($procurementRequest->isReceived())
<div class="card" style="max-width: 900px;">
    <h3 style="margin-top: 0;">Add Item</h3>
    <form action="{{ route('procurement-requests.lines.store', $procurementRequest) }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="inventory_item_id">Inventory Item (optional)</label>
                <select id="inventory_item_id" name="inventory_item_id" class="form-select" data-item-select>
                    <option value="">— Not linked —</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" data-name="{{ $item->name }}" data-unit="{{ $item->unit }}" {{ old('inventory_item_id') == $item->id ? 'selected' : '' }}>{{ $item->name }} ({{ $item->unit }})</option>
                    @endforeach
                </select>
                <p class="page-subtitle" style="margin:6px 0 0;">Linking posts a stock receipt automatically on receive.</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="item_name">Item Name *</label>
                <input type="text" id="item_name" name="item_name" value="{{ old('item_name') }}" class="form-input" required>
                @error('item_name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity">Quantity *</label>
                <input type="number" step="0.01" id="quantity" name="quantity" value="{{ old('quantity') }}" class="form-input" min="0.01" required>
                @error('quantity') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="unit">Unit</label>
                <input type="text" id="unit" name="unit" value="{{ old('unit') }}" class="form-input" placeholder="e.g. kg, litre">
            </div>
            <div class="form-group">
                <label class="form-label" for="estimated_cost">Estimated Cost (KES)</label>
                <input type="number" step="0.01" id="estimated_cost" name="estimated_cost" value="{{ old('estimated_cost') }}" class="form-input" min="0">
            </div>
        </div>
        <div style="margin-top: 12px;">
            <button type="submit" class="btn btn-primary">Add Item</button>
        </div>
    </form>
</div>
<script>
    (function () {
        var sel = document.querySelector('[data-item-select]');
        var name = document.getElementById('item_name');
        var unit = document.getElementById('unit');
        if (!sel) return;
        sel.addEventListener('change', function () {
            var opt = sel.options[sel.selectedIndex];
            if (opt && opt.value) {
                if (!name.value) name.value = opt.getAttribute('data-name') || '';
                if (!unit.value) unit.value = opt.getAttribute('data-unit') || '';
            }
        });
    })();
</script>
@endunless
@endsection
