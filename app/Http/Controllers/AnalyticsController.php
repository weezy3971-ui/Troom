<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateKpiNarrative;
use App\Models\ChartOfAccount;
use App\Models\KpiNarrative;
use App\Models\KpiSnapshot;
use App\Models\LedgerEntry;
use App\Services\Ai\AiClient;
use App\Services\AlertService;
use App\Services\KpiSnapshotService;

class AnalyticsController extends Controller
{
    public function index(AlertService $alertService)
    {
        // Read-only: the AI commentary is precomputed on a schedule / after a
        // recompute, never generated on page load.
        $narrative = KpiNarrative::current();

        $latestDate = KpiSnapshot::max('snapshot_date');

        $snapshots = $latestDate
            ? KpiSnapshot::where('snapshot_date', $latestDate)->get()->keyBy('key')
            : collect();

        // Per-KPI trend history (oldest → newest) for inline sparklines.
        $history = KpiSnapshot::orderBy('snapshot_date')
            ->get()
            ->groupBy('key')
            ->map(fn($rows) => $rows->pluck('value')->map(fn($v) => (float) $v)->values()->all());

        // Proactive alerts aggregated from across the modules.
        $alerts = $alertService->collect();

        $pnl = $this->profitAndLoss();

        return view('analytics.index', compact('snapshots', 'latestDate', 'alerts', 'history', 'pnl', 'narrative'));
    }

    /**
     * Profit & Loss / ledger summary derived from the native double-entry
     * ledger. Income accounts are credit-normal, expense accounts debit-normal.
     */
    private function profitAndLoss(): array
    {
        $accounts = ChartOfAccount::with('ledgerEntries')->orderBy('code')->get();

        // Balance from eager-loaded entries (avoids per-account queries).
        $balance = function (ChartOfAccount $a): float {
            $debit = (float) $a->ledgerEntries->sum('debit');
            $credit = (float) $a->ledgerEntries->sum('credit');

            return in_array($a->type, ['asset', 'expense'], true)
                ? $debit - $credit
                : $credit - $debit;
        };

        $income = $accounts->whereIn('type', ['income', 'revenue']);
        $expenses = $accounts->where('type', 'expense');
        $assets = $accounts->where('type', 'asset');

        $revenueTotal = (float) $income->sum($balance);
        $expenseTotal = (float) $expenses->sum($balance);
        $netProfit = $revenueTotal - $expenseTotal;

        $rows = fn ($set) => $set
            ->map(fn (ChartOfAccount $a) => [
                'code' => $a->code,
                'name' => $a->name,
                'balance' => $balance($a),
            ])
            ->sortByDesc('balance')
            ->values()
            ->all();

        return [
            'posted' => LedgerEntry::count(),
            'revenue' => $revenueTotal,
            'expenses' => $expenseTotal,
            'net' => $netProfit,
            'margin' => $revenueTotal > 0 ? ($netProfit / $revenueTotal) * 100 : null,
            'cash' => (float) $assets->sum($balance),
            'revenue_accounts' => $rows($income),
            'expense_accounts' => $rows($expenses),
            'as_of' => LedgerEntry::max('entry_date'),
        ];
    }

    public function recompute(KpiSnapshotService $service, AiClient $ai)
    {
        $service->recompute();

        // Refresh the AI commentary against the new figures. Skipped silently
        // when no API key is set so the dashboard still works without AI.
        if ($ai->isConfigured()) {
            GenerateKpiNarrative::dispatchSync();
        }

        return redirect()->route('analytics.index')
            ->with('success', 'KPI snapshots recomputed.');
    }
}
