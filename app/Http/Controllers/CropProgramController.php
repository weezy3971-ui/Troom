<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\CropProgram;
use App\Models\DailyActivity;
use Illuminate\Http\Request;

class CropProgramController extends Controller
{
    public function index(Request $request)
    {
        $query = CropProgram::with('crop')->withCount('stages')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('crop', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $programs = $query->get();
        return view('crop-programs.index', compact('programs', 'search'));
    }

    public function create()
    {
        $crops = Crop::orderBy('name')->get();
        return view('crop-programs.create', compact('crops'));
    }

    public function store(Request $request)
    {
        CropProgram::create($this->validateProgram($request));

        return redirect()->route('crop-programs.index')
            ->with('success', 'Crop program created. Add its stages next.');
    }

    public function show(CropProgram $cropProgram)
    {
        $cropProgram->load('crop', 'stages');
        $activityTypes = DailyActivity::TYPES;
        return view('crop-programs.show', compact('cropProgram', 'activityTypes'));
    }

    public function edit(CropProgram $cropProgram)
    {
        $crops = Crop::orderBy('name')->get();
        return view('crop-programs.edit', compact('cropProgram', 'crops'));
    }

    public function update(Request $request, CropProgram $cropProgram)
    {
        $cropProgram->update($this->validateProgram($request));

        return redirect()->route('crop-programs.show', $cropProgram)
            ->with('success', 'Crop program updated.');
    }

    public function destroy(CropProgram $cropProgram)
    {
        $cropProgram->delete();

        return redirect()->route('crop-programs.index')
            ->with('success', 'Crop program deleted.');
    }

    public function storeStage(Request $request, CropProgram $cropProgram)
    {
        $validated = $request->validate([
            'sequence' => 'nullable|integer|min:0',
            'name' => 'required|string|max:255',
            'activity_type' => 'nullable|string|max:50',
            'offset_days' => 'required|integer|min:0',
            'cadence' => 'nullable|string|max:100',
            'default_inputs' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
        ]);

        // Default the sequence to the next slot if not supplied.
        $validated['sequence'] = $validated['sequence'] ?? (($cropProgram->stages()->max('sequence') ?? 0) + 1);

        $cropProgram->stages()->create($validated);

        return back()->with('success', 'Stage added to program.');
    }

    public function destroyStage(CropProgram $cropProgram, \App\Models\CropProgramStage $stage)
    {
        abort_unless($stage->crop_program_id === $cropProgram->id, 404);

        $stage->delete();

        return back()->with('success', 'Stage removed.');
    }

    private function validateProgram(Request $request): array
    {
        $validated = $request->validate([
            'crop_id' => 'required|exists:crops,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
