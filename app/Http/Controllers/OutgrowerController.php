<?php

namespace App\Http\Controllers;

use App\Models\Outgrower;
use Illuminate\Http\Request;

class OutgrowerController extends Controller
{
    public function index(Request $request)
    {
        $query = Outgrower::withCount('salesOrderLines')->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $outgrowers = $query->get();
        return view('outgrowers.index', compact('outgrowers', 'search'));
    }

    public function create()
    {
        return view('outgrowers.create');
    }

    public function store(Request $request)
    {
        Outgrower::create($this->validateOutgrower($request));

        return redirect()->route('outgrowers.index')
            ->with('success', 'Outgrower added.');
    }

    public function edit(Outgrower $outgrower)
    {
        return view('outgrowers.edit', compact('outgrower'));
    }

    public function update(Request $request, Outgrower $outgrower)
    {
        $outgrower->update($this->validateOutgrower($request));

        return redirect()->route('outgrowers.index')
            ->with('success', 'Outgrower updated.');
    }

    public function destroy(Outgrower $outgrower)
    {
        $outgrower->delete();

        return redirect()->route('outgrowers.index')
            ->with('success', 'Outgrower removed.');
    }

    private function validateOutgrower(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
