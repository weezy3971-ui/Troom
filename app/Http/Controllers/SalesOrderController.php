<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Customer;
use App\Models\PackhouseLot;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesOrder::with('customer', 'crop', 'lines')->latest('order_date');

        if ($search = $request->input('search')) {
            $query->whereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%"));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $orders = $query->get();
        return view('sales-orders.index', compact('orders', 'search'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $crops = Crop::orderBy('name')->get();
        return view('sales-orders.create', compact('customers', 'crops'));
    }

    public function store(Request $request)
    {
        SalesOrder::create($this->validateOrder($request));

        return redirect()->route('sales-orders.index')
            ->with('success', 'Sales order created.');
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load('customer', 'crop', 'lines.packhouseLot.latestQualityCheck', 'dispatch');

        // Only quality-passed, unallocated lots may be added to order lines.
        $availableLots = PackhouseLot::with('harvestBatch.cropCycle.crop', 'latestQualityCheck')
            ->get()
            ->filter(fn($lot) => $lot->isQualityPassed());

        return view('sales-orders.show', compact('salesOrder', 'availableLots'));
    }

    public function edit(SalesOrder $salesOrder)
    {
        $customers = Customer::orderBy('name')->get();
        $crops = Crop::orderBy('name')->get();
        return view('sales-orders.edit', compact('salesOrder', 'customers', 'crops'));
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        $salesOrder->update($this->validateOrder($request));

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Sales order updated.');
    }

    public function destroy(SalesOrder $salesOrder)
    {
        $salesOrder->delete();

        return redirect()->route('sales-orders.index')
            ->with('success', 'Sales order deleted.');
    }

    /**
     * Allocate a quality-passed packhouse lot to this order.
     * Business rule: a failed lot cannot be added to a sales order line.
     */
    public function addLine(Request $request, SalesOrder $salesOrder)
    {
        $validated = $request->validate([
            'packhouse_lot_id' => 'required|exists:packhouse_lots,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $lot = PackhouseLot::find($validated['packhouse_lot_id']);
        if (! $lot->isQualityPassed()) {
            throw ValidationException::withMessages([
                'packhouse_lot_id' => 'This lot is not quality-passed and cannot be allocated. Re-grade or write it off first.',
            ]);
        }

        $validated['sales_order_id'] = $salesOrder->id;
        SalesOrderLine::create($validated);

        if ($salesOrder->status === 'pending') {
            $salesOrder->update(['status' => 'allocated']);
        }

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Lot allocated to order.');
    }

    public function destroyLine(SalesOrder $salesOrder, SalesOrderLine $line)
    {
        $line->delete();

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Order line removed.');
    }

    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'crop_id' => 'nullable|exists:crops,id',
            'order_date' => 'required|date',
            'requested_quantity' => 'required|numeric|min:0',
            'status' => 'required|in:pending,allocated,dispatched,fulfilled,cancelled',
            'delivery_date' => 'nullable|date',
        ]);
    }
}
