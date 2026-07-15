<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Models\Worker;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AssetCheckoutController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'outstanding');

        $query = AssetCheckout::with(['asset.farm', 'checkedOutBy'])->latest('checked_out_at');

        if ($filter === 'outstanding') {
            $query->outstanding();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('holder_name', 'like', "%{$search}%")
                  ->orWhereHas('asset', fn ($a) => $a->where('name', 'like', "%{$search}%"));
            });
        }

        $checkouts = $query->get();

        return view('checkouts.index', compact('checkouts', 'search', 'filter'));
    }

    public function create(Request $request)
    {
        // Only assets that are not already out can be checked out.
        $available = Asset::with('farm')
            ->whereDoesntHave('checkouts', fn ($q) => $q->whereNull('returned_at'))
            ->orderBy('name')
            ->get();

        $selectedAsset = $request->input('asset');
        $workers = Worker::where('is_active', true)->orderBy('name')->get();

        return view('checkouts.create', compact('available', 'selectedAsset', 'workers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            // Holder is a worker from the roster, or a new one added inline.
            'worker_id' => 'required_without:new_worker_name|nullable|exists:workers,id',
            'new_worker_name' => 'required_without:worker_id|nullable|string|max:255',
            'new_worker_phone' => 'nullable|string|max:50',
            'checked_out_at' => 'required|date',
            'expected_return_at' => 'nullable|date|after_or_equal:checked_out_at',
            'notes' => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);

        // Guard: an asset can only be in one person's hands at a time.
        if ($asset->isCheckedOut()) {
            throw ValidationException::withMessages([
                'asset_id' => "{$asset->name} is already checked out. Check it in first.",
            ]);
        }

        // Resolve the holder: a new labourer added inline takes precedence.
        if ($request->filled('new_worker_name')) {
            $worker = Worker::firstOrCreate(
                ['name' => trim($request->input('new_worker_name'))],
                ['phone' => $request->input('new_worker_phone'), 'default_rate' => 0, 'is_active' => true]
            );
        } else {
            $worker = Worker::findOrFail($validated['worker_id']);
        }

        AssetCheckout::create([
            'asset_id' => $asset->id,
            'worker_id' => $worker->id,
            'holder_name' => $worker->name,
            'checked_out_at' => $validated['checked_out_at'],
            'expected_return_at' => $validated['expected_return_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'checked_out_by' => $request->user()?->id,
        ]);

        return redirect()->route('checkouts.index')
            ->with('success', "{$asset->name} checked out to {$worker->name}.");
    }

    public function checkin(Request $request, AssetCheckout $checkout)
    {
        if ($checkout->isReturned()) {
            return redirect()->route('checkouts.index')
                ->with('error', 'This item was already checked in.');
        }

        ActivityLogger::as('checked_in', fn () => $checkout->update([
            'returned_at' => now(),
            'checked_in_by' => $request->user()?->id,
        ]));

        return redirect()->back()
            ->with('success', "{$checkout->asset->name} checked in.");
    }

    public function destroy(AssetCheckout $checkout)
    {
        $checkout->delete();

        return redirect()->route('checkouts.index')
            ->with('success', 'Checkout record deleted.');
    }
}
