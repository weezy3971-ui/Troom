<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::withCount('expenses')->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $vendors = $query->get();

        return view('vendors.index', compact('vendors', 'search'));
    }

    public function create()
    {
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        Vendor::create($this->validateVendor($request));

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor registered.');
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $vendor->update($this->validateVendor($request));

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor)
    {
        // Deleting would orphan the payee on every expense already booked
        // against them, so a vendor no longer used is deactivated instead.
        $vendor->update(['is_active' => false]);

        return redirect()->route('vendors.index')
            ->with('success', "{$vendor->name} deactivated.");
    }

    private function validateVendor(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', Vendor::TYPES),
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'kra_pin' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        // Caught here rather than at payout time: a number that cannot be
        // parsed into an MSISDN would be silently stored as null by the model,
        // and the first anyone would know is a disbursement with nowhere to go.
        if (! empty($validated['phone']) && SmsService::normalizePhone($validated['phone']) === null) {
            throw ValidationException::withMessages([
                'phone' => 'Enter a valid Kenyan mobile number (e.g. 0712 345 678) — this is where M-Pesa payouts will be sent.',
            ]);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
