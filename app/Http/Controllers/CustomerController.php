<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('salesOrders')->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%");
            });
        }

        $customers = $query->get();
        return view('customers.index', compact('customers', 'search'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        Customer::create($this->validateCustomer($request));

        return redirect()->route('customers.index')
            ->with('success', 'Customer registered.');
    }

    public function show(Customer $customer)
    {
        $customer->load('salesOrders.crop', 'salesOrders.lines');
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validateCustomer($request));

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted.');
    }

    private function validateCustomer(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'contract_terms' => 'nullable|string',
            'price_list' => 'nullable|string|max:255',
        ]);

        // The model stores an unparseable number as null; rejecting it here
        // means nobody discovers the gap when a payment prompt fails to send.
        if (! empty($validated['phone']) && SmsService::normalizePhone($validated['phone']) === null) {
            throw ValidationException::withMessages([
                'phone' => 'Enter a valid Kenyan mobile number (e.g. 0712 345 678).',
            ]);
        }

        return $validated;
    }
}
