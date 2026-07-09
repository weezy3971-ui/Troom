<?php

namespace App\Http\Controllers;

use App\Models\CostAllocation;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::with('farm', 'transactions')->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $items = $query->get();
        return view('inventory-items.index', compact('items', 'search'));
    }

    public function create()
    {
        $farms = Farm::orderBy('name')->get();
        return view('inventory-items.create', compact('farms'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateItem($request);

        InventoryItem::create($validated);

        return redirect()->route('inventory-items.index')
            ->with('success', 'Inventory item registered.');
    }

    public function show(InventoryItem $inventoryItem)
    {
        $inventoryItem->load('farm', 'transactions.cropCycle');
        $cropCycles = CropCycle::with('block')->orderBy('season_name')->get();
        return view('inventory-items.show', compact('inventoryItem', 'cropCycles'));
    }

    public function edit(InventoryItem $inventoryItem)
    {
        $farms = Farm::orderBy('name')->get();
        return view('inventory-items.edit', compact('inventoryItem', 'farms'));
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $this->validateItem($request);

        $inventoryItem->update($validated);

        return redirect()->route('inventory-items.show', $inventoryItem)
            ->with('success', 'Inventory item updated.');
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();

        return redirect()->route('inventory-items.index')
            ->with('success', 'Inventory item deleted.');
    }

    /**
     * Record a stock movement against an item. Issues tied to a crop cycle
     * automatically feed Cost Allocation (source type: inventory).
     */
    public function storeTransaction(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'type' => 'required|in:receipt,issue,adjustment',
            'quantity' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'crop_cycle_id' => 'nullable|exists:crop_cycles,id',
        ]);

        $validated['inventory_item_id'] = $inventoryItem->id;
        $validated['farm_id'] = $inventoryItem->farm_id;
        $validated['cost'] = $validated['cost'] ?? 0;

        // Business rule: issue transactions cannot drive stock below zero.
        if ($validated['type'] === 'issue') {
            $inventoryItem->load('transactions');
            $currentStock = $inventoryItem->currentStock();
            if ((float) $validated['quantity'] > $currentStock) {
                return back()->withInput()->with(
                    'error',
                    "Cannot issue {$validated['quantity']} {$inventoryItem->unit}: only "
                    . number_format($currentStock, 2) . " {$inventoryItem->unit} in stock."
                );
            }
        }

        $transaction = InventoryTransaction::create($validated);

        if ($transaction->type === 'issue' && $transaction->crop_cycle_id) {
            CostAllocation::create([
                'crop_cycle_id' => $transaction->crop_cycle_id,
                'block_id' => null,
                'source_type' => 'inventory',
                'source_id' => $transaction->id,
                'amount' => $transaction->cost,
                'allocation_date' => $transaction->transaction_date,
                'description' => "Inventory issue: {$inventoryItem->name} ({$transaction->quantity} {$inventoryItem->unit})",
            ]);
        }

        return redirect()->route('inventory-items.show', $inventoryItem)
            ->with('success', 'Stock movement recorded.');
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'farm_id' => 'nullable|exists:farms,id',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'reorder_level' => 'required|numeric|min:0',
        ]);
    }
}
