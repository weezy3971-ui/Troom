<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Customer;
use App\Models\Outgrower;
use App\Models\PackhouseLot;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Support\ActivityLogger;
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

        $outgrowers = Outgrower::where('is_active', true)->orderBy('name')->get();

        return view('sales-orders.show', compact('salesOrder', 'availableLots', 'outgrowers'));
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
            'source' => 'required|in:lot,outgrower',
            'packhouse_lot_id' => 'required_if:source,lot|nullable|exists:packhouse_lots,id',
            'outgrower_id' => 'required_if:source,outgrower|nullable|exists:outgrowers,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
        ]);

        if ($validated['source'] === 'lot') {
            $lot = PackhouseLot::find($validated['packhouse_lot_id']);
            if (! $lot->isQualityPassed()) {
                throw ValidationException::withMessages([
                    'packhouse_lot_id' => 'This lot is not quality-passed and cannot be allocated. Re-grade or write it off first.',
                ]);
            }
            // A lot line never carries an outgrower, and vice versa.
            $validated['outgrower_id'] = null;
        } else {
            $validated['packhouse_lot_id'] = null;
        }

        $validated['sales_order_id'] = $salesOrder->id;
        SalesOrderLine::create($validated);

        if ($salesOrder->status === 'pending') {
            $salesOrder->update(['status' => 'allocated']);
        }

        $msg = $validated['source'] === 'outgrower'
            ? 'Outgrower produce added to order.'
            : 'Lot allocated to order.';

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', $msg);
    }

    public function destroyLine(SalesOrder $salesOrder, SalesOrderLine $line)
    {
        $line->delete();

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Order line removed.');
    }

    /**
     * Record what the buyer actually accepted vs rejected/returned and any
     * amount repaid ("out of 1000 kilos we need 950, then 50 you'll reject").
     */
    public function recordDelivery(Request $request, SalesOrder $salesOrder)
    {
        $validated = $request->validate([
            'delivered_quantity' => 'required|numeric|min:0',
            'rejected_quantity' => 'nullable|numeric|min:0',
            'returned_quantity' => 'nullable|numeric|min:0',
            'amount_repaid' => 'nullable|numeric|min:0',
        ]);

        ActivityLogger::as('delivered', fn () => $salesOrder->update([
            'delivered_quantity' => $validated['delivered_quantity'],
            'rejected_quantity' => $validated['rejected_quantity'] ?? 0,
            'returned_quantity' => $validated['returned_quantity'] ?? 0,
            'amount_repaid' => $validated['amount_repaid'] ?? 0,
        ]));

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', 'Delivery outcome recorded.');
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
