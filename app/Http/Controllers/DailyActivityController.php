<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\CropCycle;
use App\Models\DailyActivity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DailyActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = DailyActivity::with('block.farm', 'cropCycle', 'logger')->latest('activity_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('activity_type', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('block', fn($b) => $b->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('logger', fn($l) => $l->where('name', 'like', "%{$search}%"));
            });
        }

        $activities = $query->get();
        return view('daily-activities.index', compact('activities', 'search'));
    }

    public function create()
    {
        $blocks = Block::with('farm')->orderBy('name')->get();
        $cropCycles = CropCycle::with('block')->orderBy('season_name')->get();
        $types = DailyActivity::TYPES;
        return view('daily-activities.create', compact('blocks', 'cropCycles', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateActivity($request);

        $validated['logged_by'] = $request->user()?->id;

        DailyActivity::create($validated);

        return redirect()->route('daily-activities.index')
            ->with('success', 'Activity logged successfully.');
    }

    public function show(DailyActivity $dailyActivity)
    {
        $dailyActivity->load('block.farm', 'cropCycle.crop', 'logger');
        return view('daily-activities.show', compact('dailyActivity'));
    }

    public function edit(DailyActivity $dailyActivity)
    {
        $blocks = Block::with('farm')->orderBy('name')->get();
        $cropCycles = CropCycle::with('block')->orderBy('season_name')->get();
        $types = DailyActivity::TYPES;
        return view('daily-activities.edit', compact('dailyActivity', 'blocks', 'cropCycles', 'types'));
    }

    public function update(Request $request, DailyActivity $dailyActivity)
    {
        $validated = $this->validateActivity($request);

        $dailyActivity->update($validated);

        return redirect()->route('daily-activities.show', $dailyActivity)
            ->with('success', 'Activity updated successfully.');
    }

    public function destroy(DailyActivity $dailyActivity)
    {
        $dailyActivity->delete();

        return redirect()->route('daily-activities.index')
            ->with('success', 'Activity deleted successfully.');
    }

    /**
     * Business rule: photo and GPS capture is required for activity types tied to
     * compliance (e.g. spraying).
     */
    private function validateActivity(Request $request): array
    {
        $isCompliance = in_array($request->input('activity_type'), DailyActivity::COMPLIANCE_TYPES, true);

        return $request->validate([
            'block_id' => 'required|exists:blocks,id',
            'crop_cycle_id' => 'nullable|exists:crop_cycles,id',
            'activity_type' => ['required', Rule::in(DailyActivity::TYPES)],
            'activity_date' => 'required|date',
            'description' => 'nullable|string',
            'photo_path' => [$isCompliance ? 'required' : 'nullable', 'string', 'max:255'],
            'gps_location' => [$isCompliance ? 'required' : 'nullable', 'string', 'max:255'],
        ], [
            'photo_path.required' => 'A photo is required for compliance activities such as spraying.',
            'gps_location.required' => 'GPS location is required for compliance activities such as spraying.',
        ]);
    }
}
