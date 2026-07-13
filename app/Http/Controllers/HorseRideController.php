<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Models\Horse;
use App\Models\HorseRide;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class HorseRideController extends Controller
{
    public function index(Request $request)
    {
        $query = HorseRide::with(['horse', 'guide'])->latest('start_time');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $rides = $query->get();
        return view('rides.index', compact('rides', 'search'));
    }

    public function create()
    {
        return view('rides.create');
    }

    /**
     * The ride desk records the booking and payment; a receipt number is
     * generated immediately. Horse and guide are assigned later.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'start_time' => 'required|date',
            'duration_minutes' => 'required|integer|min:1|max:1440',
            'amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:paid,unpaid',
            'notes' => 'nullable|string',
        ]);

        $start = Carbon::parse($validated['start_time']);
        $validated['end_time'] = $start->copy()->addMinutes((int) $validated['duration_minutes']);
        $validated['status'] = 'pending_assignment';
        $validated['receipt_number'] = 'RCP-PENDING';

        $ride = HorseRide::create($validated);
        $ride->update(['receipt_number' => 'RCP-' . str_pad((string) $ride->id, 6, '0', STR_PAD_LEFT)]);

        return redirect()->route('rides.show', $ride)
            ->with('success', "Ride booked. Receipt {$ride->receipt_number} generated — hand to the stable manager to assign a horse and guide.");
    }

    public function show(HorseRide $ride)
    {
        $ride->load(['horse', 'guide']);

        $start = $ride->start_time;
        $end = $ride->end_time;

        // Horses free for this ride's window (respecting each horse's rest buffer).
        $horses = Horse::where('is_active', true)->orderBy('name')->get()
            ->filter(fn (Horse $h) => $h->id === $ride->horse_id || $h->isFreeFor($start, $end, $ride->id))
            ->values();

        $guides = Guide::where('is_active', true)->orderBy('name')->get();

        return view('rides.show', compact('ride', 'horses', 'guides'));
    }

    public function edit(HorseRide $ride)
    {
        return view('rides.edit', compact('ride'));
    }

    /**
     * Edit ride details — most commonly to update payment status, but customer,
     * time, duration and amount are editable too.
     */
    public function update(Request $request, HorseRide $ride)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'start_time' => 'required|date',
            'duration_minutes' => 'required|integer|min:1|max:1440',
            'amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:paid,unpaid',
            'notes' => 'nullable|string',
        ]);

        $start = Carbon::parse($validated['start_time']);
        $validated['end_time'] = $start->copy()->addMinutes((int) $validated['duration_minutes']);

        // If a horse is assigned and the window moved, make sure it is still
        // free across the new window (ignoring this ride's own booking).
        if ($ride->horse_id) {
            $horse = Horse::find($ride->horse_id);
            if ($horse && ! $horse->isFreeFor($start, $validated['end_time'], $ride->id)) {
                throw ValidationException::withMessages([
                    'start_time' => "{$horse->name} is not free for the new time window. Reassign the horse or pick another time.",
                ]);
            }
        }

        $ride->update($validated);

        return redirect()->route('rides.show', $ride)->with('success', 'Ride updated.');
    }

    /**
     * Stable manager assigns a horse and guide. The horse must be free across
     * the ride window including its rest period.
     */
    public function assign(Request $request, HorseRide $ride)
    {
        $validated = $request->validate([
            'horse_id' => 'required|exists:horses,id',
            'guide_id' => 'required|exists:guides,id',
        ]);

        $horse = Horse::findOrFail($validated['horse_id']);

        if (! $horse->isFreeFor($ride->start_time, $ride->end_time)) {
            throw ValidationException::withMessages([
                'horse_id' => "{$horse->name} is not free for this ride window (on another ride or resting).",
            ]);
        }

        $ride->update([
            'horse_id' => $horse->id,
            'guide_id' => $validated['guide_id'],
            'status' => 'assigned',
        ]);

        return redirect()->route('rides.show', $ride)
            ->with('success', "{$horse->name} and guide assigned to this ride.");
    }

    public function cancel(HorseRide $ride)
    {
        $ride->update(['status' => 'cancelled']);

        return redirect()->route('rides.show', $ride)
            ->with('success', 'Ride cancelled. The horse is freed.');
    }

    /**
     * Print-friendly receipt (browser "Print → Save as PDF").
     */
    public function receipt(HorseRide $ride)
    {
        // A receipt can only be printed once the ride is paid.
        if ($ride->payment_status !== 'paid') {
            return redirect()->route('rides.show', $ride)
                ->with('error', 'This ride is unpaid — mark it as paid before printing a receipt.');
        }

        $ride->load(['horse', 'guide']);
        return view('rides.receipt', compact('ride'));
    }
}
