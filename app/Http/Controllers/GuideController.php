<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use Illuminate\Http\Request;

class GuideController extends Controller
{
    public function index(Request $request)
    {
        $query = Guide::withCount('rides')->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        }

        $guides = $query->get();
        return view('guides.index', compact('guides', 'search'));
    }

    public function create()
    {
        return view('guides.create');
    }

    public function store(Request $request)
    {
        Guide::create($this->validateGuide($request));

        return redirect()->route('guides.index')->with('success', 'Guide added.');
    }

    public function edit(Guide $guide)
    {
        return view('guides.edit', compact('guide'));
    }

    public function update(Request $request, Guide $guide)
    {
        $guide->update($this->validateGuide($request));

        return redirect()->route('guides.index')->with('success', 'Guide updated.');
    }

    public function destroy(Guide $guide)
    {
        $guide->delete();

        return redirect()->route('guides.index')->with('success', 'Guide removed.');
    }

    private function validateGuide(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
