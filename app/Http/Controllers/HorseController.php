<?php

namespace App\Http\Controllers;

use App\Models\Horse;
use Illuminate\Http\Request;

class HorseController extends Controller
{
    public function index(Request $request)
    {
        $query = Horse::with(['rides' => fn ($q) => $q->whereIn('status', ['assigned', 'completed'])])
            ->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('breed', 'like', "%{$search}%");
        }

        $horses = $query->get();
        return view('horses.index', compact('horses', 'search'));
    }

    public function create()
    {
        return view('horses.create');
    }

    public function store(Request $request)
    {
        Horse::create($this->validateHorse($request));

        return redirect()->route('horses.index')->with('success', 'Horse added.');
    }

    public function edit(Horse $horse)
    {
        return view('horses.edit', compact('horse'));
    }

    public function update(Request $request, Horse $horse)
    {
        $horse->update($this->validateHorse($request));

        return redirect()->route('horses.index')->with('success', 'Horse updated.');
    }

    public function destroy(Horse $horse)
    {
        $horse->delete();

        return redirect()->route('horses.index')->with('success', 'Horse removed.');
    }

    private function validateHorse(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'breed' => 'nullable|string|max:255',
            'rest_minutes' => 'required|integer|min:0|max:1440',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
