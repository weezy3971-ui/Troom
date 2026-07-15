<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAiReport;
use App\Models\AiReport;
use App\Services\Ai\AiClient;
use Illuminate\Http\Request;

class AiReportController extends Controller
{
    public function index()
    {
        $reports = AiReport::with('generatedBy')->latest()->get();
        $configured = app(AiClient::class)->isConfigured();

        return view('ai-reports.index', compact('reports', 'configured'));
    }

    public function create()
    {
        $configured = app(AiClient::class)->isConfigured();

        return view('ai-reports.create', compact('configured'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(AiReport::TYPES)),
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
        ]);

        $report = AiReport::create([
            'type' => $validated['type'],
            'title' => AiReport::TYPES[$validated['type']] . ' — ' . now()->format('M d, Y'),
            'period_start' => $validated['period_start'] ?? null,
            'period_end' => $validated['period_end'] ?? null,
            'status' => 'pending',
            'generated_by' => $request->user()?->id,
        ]);

        // Synchronous today so the report is ready on redirect. Switch to
        // GenerateAiReport::dispatch(...) once a queue worker is running.
        GenerateAiReport::dispatchSync($report->id);

        $report->refresh();

        return redirect()->route('ai-reports.show', $report)
            ->with($report->isFailed() ? 'error' : 'success',
                $report->isFailed()
                    ? "Report generation failed: {$report->error}"
                    : 'Report generated.');
    }

    public function show(AiReport $aiReport)
    {
        return view('ai-reports.show', ['report' => $aiReport]);
    }

    public function regenerate(AiReport $aiReport)
    {
        $aiReport->update(['status' => 'pending', 'error' => null]);
        GenerateAiReport::dispatchSync($aiReport->id);
        $aiReport->refresh();

        return redirect()->route('ai-reports.show', $aiReport)
            ->with($aiReport->isFailed() ? 'error' : 'success',
                $aiReport->isFailed() ? "Regeneration failed: {$aiReport->error}" : 'Report regenerated.');
    }

    public function destroy(AiReport $aiReport)
    {
        $aiReport->delete();

        return redirect()->route('ai-reports.index')->with('success', 'Report deleted.');
    }
}
