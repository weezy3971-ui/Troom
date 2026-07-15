<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\CropCycle;
use App\Models\WeighScaleReading;
use App\Models\Worker;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WeighScaleReadingController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'new');

        $query = WeighScaleReading::with('weighedByWorker', 'block', 'cropCycle', 'acknowledgedBy')
            ->latest('weighed_at');

        if ($filter === 'new') {
            $query->unacknowledged();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('weighed_by_name', 'like', "%{$search}%")
                  ->orWhere('item', 'like', "%{$search}%")
                  ->orWhere('device_name', 'like', "%{$search}%");
            });
        }

        $readings = $query->get();
        $newCount = WeighScaleReading::unacknowledged()->count();

        return view('weigh-scale-readings.index', compact('readings', 'search', 'filter', 'newCount'));
    }

    public function create()
    {
        $workers = Worker::where('is_active', true)->orderBy('name')->get();
        $blocks = Block::with('farm')->orderBy('name')->get();
        $cropCycles = CropCycle::with('block')->orderBy('season_name')->get();
        return view('weigh-scale-readings.create', compact('workers', 'blocks', 'cropCycles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'weighed_by_worker_id' => 'nullable|exists:workers,id',
            'weighed_by_name' => 'required|string|max:255',
            'item' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:20',
            'weighed_at' => 'required|date',
            'block_id' => 'nullable|exists:blocks,id',
            'crop_cycle_id' => 'nullable|exists:crop_cycles,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $validated['unit'] = $validated['unit'] ?? 'kg';
        $validated['source'] = 'manual';

        WeighScaleReading::create($validated);

        return redirect()->route('weigh-scale-readings.index')
            ->with('success', 'Weigh reading recorded.');
    }

    /** Mark a reading as reviewed. */
    public function acknowledge(Request $request, WeighScaleReading $weighScaleReading)
    {
        if (! $weighScaleReading->isAcknowledged()) {
            ActivityLogger::as('acknowledged', fn () => $weighScaleReading->update([
                'acknowledged_at' => now(),
                'acknowledged_by' => $request->user()?->id,
            ]));
        }

        return back()->with('success', 'Reading acknowledged.');
    }

    public function destroy(WeighScaleReading $weighScaleReading)
    {
        $weighScaleReading->delete();

        return back()->with('success', 'Reading deleted.');
    }

    /**
     * Device ingest endpoint. The digital scale POSTs a reading here with a
     * shared bearer token. Stateless (no session/CSRF) — wired via routes/api.php.
     * Idempotent on (device_name, external_id) so retries don't duplicate.
     */
    public function ingest(Request $request): JsonResponse
    {
        $expected = config('services.weigh_scale.token');

        // Fail closed: without a configured token the endpoint is disabled.
        if (empty($expected) || ! hash_equals($expected, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'device_name' => 'nullable|string|max:255',
            'external_id' => 'nullable|string|max:255',
            'weighed_by_name' => 'required|string|max:255',
            'item' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:20',
            'weighed_at' => 'nullable|date',
        ]);

        // Idempotency: a repeated device id + reading id returns the first record.
        if (! empty($data['external_id'])) {
            $existing = WeighScaleReading::where('device_name', $data['device_name'] ?? null)
                ->where('external_id', $data['external_id'])
                ->first();

            if ($existing) {
                return response()->json(['id' => $existing->id, 'status' => 'duplicate'], 200);
            }
        }

        $reading = WeighScaleReading::create([
            'device_name' => $data['device_name'] ?? null,
            'external_id' => $data['external_id'] ?? null,
            'weighed_by_name' => $data['weighed_by_name'],
            'item' => $data['item'],
            'weight' => $data['weight'],
            'unit' => $data['unit'] ?? 'kg',
            'weighed_at' => isset($data['weighed_at']) ? Carbon::parse($data['weighed_at']) : now(),
            'source' => 'device',
        ]);

        return response()->json(['id' => $reading->id, 'status' => 'created'], 201);
    }
}
