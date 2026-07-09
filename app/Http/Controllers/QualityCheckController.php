<?php

namespace App\Http\Controllers;

use App\Models\PackhouseLot;
use App\Models\QualityCheck;
use App\Models\User;
use Illuminate\Http\Request;

class QualityCheckController extends Controller
{
    public function index(Request $request)
    {
        $query = QualityCheck::with('packhouseLot.harvestBatch.cropCycle.crop', 'inspector')->latest('check_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('result', 'like', "%{$search}%")
                  ->orWhereHas('packhouseLot', fn($l) => $l->where('lot_number', 'like', "%{$search}%")
                        ->orWhere('traceability_code', 'like', "%{$search}%"));
            });
        }

        $checks = $query->get();
        return view('quality-checks.index', compact('checks', 'search'));
    }

    public function create()
    {
        $lots = PackhouseLot::with('harvestBatch.cropCycle.crop')->latest('pack_date')->get();
        $inspectors = User::orderBy('name')->get();
        return view('quality-checks.create', compact('lots', 'inspectors'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCheck($request);
        $validated['parameters'] = $this->parseParameters($request->input('parameters'));

        QualityCheck::create($validated);

        return redirect()->route('quality-checks.index')
            ->with('success', 'Quality check recorded.');
    }

    public function show(QualityCheck $qualityCheck)
    {
        $qualityCheck->load('packhouseLot.harvestBatch.cropCycle.crop', 'inspector');
        return view('quality-checks.show', compact('qualityCheck'));
    }

    public function edit(QualityCheck $qualityCheck)
    {
        $lots = PackhouseLot::with('harvestBatch.cropCycle.crop')->latest('pack_date')->get();
        $inspectors = User::orderBy('name')->get();
        return view('quality-checks.edit', compact('qualityCheck', 'lots', 'inspectors'));
    }

    public function update(Request $request, QualityCheck $qualityCheck)
    {
        $validated = $this->validateCheck($request);
        $validated['parameters'] = $this->parseParameters($request->input('parameters'));

        $qualityCheck->update($validated);

        return redirect()->route('quality-checks.show', $qualityCheck)
            ->with('success', 'Quality check updated.');
    }

    public function destroy(QualityCheck $qualityCheck)
    {
        $qualityCheck->delete();

        return redirect()->route('quality-checks.index')
            ->with('success', 'Quality check deleted.');
    }

    /**
     * Parameters are captured as free-form "key: value" lines and stored as JSON.
     */
    private function parseParameters(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $params = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode(':', $line, 2));
            if ($key !== '') {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    private function validateCheck(Request $request): array
    {
        return $request->validate([
            'packhouse_lot_id' => 'required|exists:packhouse_lots,id',
            'check_date' => 'required|date',
            'result' => 'required|in:pass,fail',
            'inspector_id' => 'nullable|exists:users,id',
            'photo_path' => 'nullable|string|max:255',
        ]);
    }
}
