<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Block;
use App\Models\IrrigationLog;
use Illuminate\Http\Request;

class IrrigationLogController extends Controller
{
    public function index(Request $request)
    {
        $query = IrrigationLog::with('block.farm', 'pump', 'logger')->latest('log_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('block', fn($b) => $b->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('pump', fn($p) => $p->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('logger', fn($l) => $l->where('name', 'like', "%{$search}%"));
            });
        }

        $logs = $query->get();
        return view('irrigation-logs.index', compact('logs', 'search'));
    }

    public function create()
    {
        $blocks = Block::with('farm')->orderBy('name')->get();
        $pumps = Asset::where('type', 'pump')->orderBy('name')->get();
        return view('irrigation-logs.create', compact('blocks', 'pumps'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateLog($request);

        if ($error = $this->pumpNotOperational($validated['pump_asset_id'] ?? null)) {
            return back()->withInput()->with('error', $error);
        }

        $validated['logged_by'] = $request->user()?->id;

        IrrigationLog::create($validated);

        return redirect()->route('irrigation-logs.index')
            ->with('success', 'Irrigation session logged successfully.');
    }

    public function show(IrrigationLog $irrigationLog)
    {
        $irrigationLog->load('block.farm', 'pump', 'logger');
        return view('irrigation-logs.show', compact('irrigationLog'));
    }

    public function edit(IrrigationLog $irrigationLog)
    {
        $blocks = Block::with('farm')->orderBy('name')->get();
        $pumps = Asset::where('type', 'pump')->orderBy('name')->get();
        return view('irrigation-logs.edit', compact('irrigationLog', 'blocks', 'pumps'));
    }

    public function update(Request $request, IrrigationLog $irrigationLog)
    {
        $validated = $this->validateLog($request);

        if ($error = $this->pumpNotOperational($validated['pump_asset_id'] ?? null)) {
            return back()->withInput()->with('error', $error);
        }

        $irrigationLog->update($validated);

        return redirect()->route('irrigation-logs.show', $irrigationLog)
            ->with('success', 'Irrigation log updated successfully.');
    }

    public function destroy(IrrigationLog $irrigationLog)
    {
        $irrigationLog->delete();

        return redirect()->route('irrigation-logs.index')
            ->with('success', 'Irrigation log deleted successfully.');
    }

    private function validateLog(Request $request): array
    {
        return $request->validate([
            'block_id'       => 'required|exists:blocks,id',
            'pump_asset_id'  => 'nullable|exists:assets,id',
            'log_date'       => 'required|date',
            'start_time'     => 'nullable|date_format:H:i',
            // end_time must be after start_time only when start_time is also supplied.
            'end_time'       => 'nullable|date_format:H:i|required_with:start_time|after:start_time',
            'hours'          => 'required|numeric|min:0',
            'water_volume'   => 'nullable|numeric|min:0',
        ], [
            'end_time.required_with' => 'End time is required when a start time is provided.',
            'end_time.after'         => 'End time must be after start time.',
        ]);
    }

    /**
     * Business rule: an irrigation log cannot be created against a pump asset
     * that is not in operational status.
     */
    private function pumpNotOperational(?int $pumpId): ?string
    {
        if (! $pumpId) {
            return null;
        }

        $pump = Asset::find($pumpId);

        if ($pump && ! $pump->isOperational()) {
            return "Pump \"{$pump->name}\" is not operational (status: {$pump->status}). Choose an operational pump.";
        }

        return null;
    }
}
