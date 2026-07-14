<?php

namespace App\Http\Controllers;

use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestLine;
use Illuminate\Http\Request;

class ProcurementRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ProcurementRequest::with('farm', 'cropCycle', 'lines')->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhereHas('lines', fn ($l) => $l->where('item_name', 'like', "%{$search}%"));
            });
        }

        $requests = $query->get();
        return view('procurement-requests.index', compact('requests', 'search', 'status'));
    }

    public function create()
    {
        $farms = Farm::orderBy('name')->get();
        $cropCycles = CropCycle::with('block')->orderBy('season_name')->get();
        return view('procurement-requests.create', compact('farms', 'cropCycles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'farm_id' => 'nullable|exists:farms,id',
            'crop_cycle_id' => 'nullable|exists:crop_cycles,id',
            'needed_by' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $validated['requested_by'] = $request->user()?->id;
        $validated['status'] = 'requested';

        $procurementRequest = ProcurementRequest::create($validated);

        return redirect()->route('procurement-requests.show', $procurementRequest)
            ->with('success', 'Procurement request created. Add the items needed.');
    }

    public function show(ProcurementRequest $procurementRequest)
    {
        $procurementRequest->load('farm', 'cropCycle.block', 'requestedBy', 'lines.inventoryItem');
        $items = InventoryItem::orderBy('name')->get();
        return view('procurement-requests.show', compact('procurementRequest', 'items'));
    }

    public function destroy(ProcurementRequest $procurementRequest)
    {
        $procurementRequest->delete();

        return redirect()->route('procurement-requests.index')
            ->with('success', 'Procurement request deleted.');
    }

    public function storeLine(Request $request, ProcurementRequest $procurementRequest)
    {
        abort_if($procurementRequest->isReceived(), 403, 'Cannot change a received request.');

        $validated = $request->validate([
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'estimated_cost' => 'nullable|numeric|min:0',
        ]);

        // If an inventory item is chosen, borrow its name/unit for the label.
        if (! empty($validated['inventory_item_id'])) {
            $item = InventoryItem::find($validated['inventory_item_id']);
            $validated['item_name'] = $validated['item_name'] ?: $item?->name;
            $validated['unit'] = $validated['unit'] ?: $item?->unit;
        }

        $procurementRequest->lines()->create($validated);

        return back()->with('success', 'Item added to request.');
    }

    public function destroyLine(ProcurementRequest $procurementRequest, ProcurementRequestLine $line)
    {
        abort_unless($line->procurement_request_id === $procurementRequest->id, 404);
        abort_if($procurementRequest->isReceived(), 403, 'Cannot change a received request.');

        $line->delete();

        return back()->with('success', 'Item removed.');
    }

    /** requested → ordered */
    public function markOrdered(ProcurementRequest $procurementRequest)
    {
        if ($procurementRequest->status !== 'requested') {
            return back()->with('error', 'Only a requested order can be marked ordered.');
        }

        $procurementRequest->update(['status' => 'ordered', 'ordered_at' => now()]);

        return back()->with('success', 'Marked as ordered.');
    }

    /**
     * ordered/requested → received. Each line linked to an inventory item posts
     * a receipt transaction so the store reflects the purchase (note 3: "record
     * as inventory").
     */
    public function markReceived(ProcurementRequest $procurementRequest)
    {
        if ($procurementRequest->isReceived()) {
            return back()->with('error', 'This request was already received.');
        }

        $procurementRequest->load('lines.inventoryItem');

        foreach ($procurementRequest->lines as $line) {
            if (! $line->inventory_item_id) {
                continue;
            }

            InventoryTransaction::create([
                'inventory_item_id' => $line->inventory_item_id,
                'farm_id' => $procurementRequest->farm_id ?? $line->inventoryItem?->farm_id,
                'crop_cycle_id' => $procurementRequest->crop_cycle_id,
                'type' => 'receipt',
                'quantity' => $line->quantity,
                'transaction_date' => now()->toDateString(),
                'reference' => "Procurement #{$procurementRequest->id}",
                'cost' => $line->estimated_cost ?? 0,
            ]);
        }

        $procurementRequest->update(['status' => 'received', 'received_at' => now()]);

        return back()->with('success', 'Marked as received. Linked items added to inventory.');
    }
}
