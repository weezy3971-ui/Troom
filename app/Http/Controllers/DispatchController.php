<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Dispatch;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Dispatch::with('salesOrder.customer', 'vehicle', 'driver')->latest('dispatch_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('route', 'like', "%{$search}%")
                  ->orWhereHas('salesOrder.customer', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $dispatches = $query->get();
        return view('dispatches.index', compact('dispatches', 'search'));
    }

    public function create()
    {
        return view('dispatches.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateDispatch($request);

        $dispatch = Dispatch::create($validated);
        $dispatch->salesOrder->update(['status' => 'dispatched']);

        return redirect()->route('dispatches.index')
            ->with('success', 'Dispatch scheduled.');
    }

    public function show(Dispatch $dispatch)
    {
        $dispatch->load('salesOrder.customer', 'salesOrder.lines', 'vehicle', 'driver');
        return view('dispatches.show', compact('dispatch'));
    }

    public function edit(Dispatch $dispatch)
    {
        return view('dispatches.edit', array_merge(['dispatch' => $dispatch], $this->formData()));
    }

    public function update(Request $request, Dispatch $dispatch)
    {
        $validated = $this->validateDispatch($request);

        $dispatch->update($validated);

        // On delivery completion, mark the order fulfilled.
        if ($dispatch->status === 'delivered') {
            $dispatch->salesOrder->update(['status' => 'fulfilled']);
        }

        return redirect()->route('dispatches.show', $dispatch)
            ->with('success', 'Dispatch updated.');
    }

    public function destroy(Dispatch $dispatch)
    {
        $dispatch->delete();

        return redirect()->route('dispatches.index')
            ->with('success', 'Dispatch deleted.');
    }

    private function formData(): array
    {
        return [
            'orders' => SalesOrder::with('customer')->whereIn('status', ['allocated', 'dispatched'])->latest('order_date')->get(),
            'vehicles' => Asset::where('type', 'vehicle')->orderBy('name')->get(),
            'drivers' => User::where('role', 'driver')->orderBy('name')->get(),
        ];
    }

    private function validateDispatch(Request $request): array
    {
        return $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'vehicle_asset_id' => 'nullable|exists:assets,id',
            'driver_id' => 'nullable|exists:users,id',
            'dispatch_date' => 'required|date',
            'route' => 'nullable|string|max:255',
            'status' => 'required|in:scheduled,in_transit,delivered,cancelled',
        ]);
    }
}
