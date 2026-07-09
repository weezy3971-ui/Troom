<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\CostAllocation;
use App\Models\CropCycle;
use App\Models\LedgerEntry;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function index()
    {
        $accounts = ChartOfAccount::with('ledgerEntries')->orderBy('code')->get();

        $entries = LedgerEntry::with('account')->latest('entry_date')->latest('id')->take(50)->get();

        $totalDebit = (float) LedgerEntry::sum('debit');
        $totalCredit = (float) LedgerEntry::sum('credit');

        // Business rule: budget_exceeded alert when a cycle's actual cost
        // surpasses its seasonal budget.
        $exceededCycles = CropCycle::with('seasonalBudget', 'block', 'crop')
            ->get()
            ->filter(fn($c) => $c->isBudgetExceeded());

        $unpostedAllocations = $this->unpostedAllocationCount();

        return view('finance.index', compact(
            'accounts', 'entries', 'totalDebit', 'totalCredit', 'exceededCycles', 'unpostedAllocations'
        ));
    }

    public function ledger(Request $request)
    {
        $query = LedgerEntry::with('account')->latest('entry_date')->latest('id');

        if ($accountId = $request->input('account')) {
            $query->where('account_id', $accountId);
        }

        $entries = $query->get();
        $accounts = ChartOfAccount::orderBy('code')->get();

        return view('finance.ledger', compact('entries', 'accounts'));
    }

    /**
     * Auto-populate the ledger from Cost Allocations and fulfilled sales.
     * Every transaction posts a balanced debit/credit pair (double-entry).
     */
    public function post()
    {
        $expense = $this->account('5000', 'Farm Operating Costs', 'expense');
        $cash = $this->account('1000', 'Cash & Bank', 'asset');
        $revenue = $this->account('4000', 'Produce Sales', 'income');

        $posted = 0;

        DB::transaction(function () use ($expense, $cash, $revenue, &$posted) {
            // Cost allocations -> Dr Expense, Cr Cash
            $allocations = CostAllocation::whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('ledger_entries')
                  ->where('reference_type', 'cost_allocation')
                  ->whereColumn('reference_id', 'cost_allocations.id');
            })->get();

            foreach ($allocations as $alloc) {
                LedgerEntry::create([
                    'entry_date' => $alloc->allocation_date,
                    'account_id' => $expense->id,
                    'debit' => $alloc->amount,
                    'credit' => 0,
                    'reference_type' => 'cost_allocation',
                    'reference_id' => $alloc->id,
                    'description' => $alloc->description ?? "Cost allocation #{$alloc->id}",
                ]);
                LedgerEntry::create([
                    'entry_date' => $alloc->allocation_date,
                    'account_id' => $cash->id,
                    'debit' => 0,
                    'credit' => $alloc->amount,
                    'reference_type' => 'cost_allocation',
                    'reference_id' => $alloc->id,
                    'description' => $alloc->description ?? "Cost allocation #{$alloc->id}",
                ]);
                $posted++;
            }

            // Fulfilled sales orders -> Dr Cash, Cr Revenue
            $orders = SalesOrder::with('lines')
                ->where('status', 'fulfilled')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('ledger_entries')
                      ->where('reference_type', 'sale')
                      ->whereColumn('reference_id', 'sales_orders.id');
                })->get();

            foreach ($orders as $order) {
                $value = $order->orderValue();
                if ($value <= 0) {
                    continue;
                }
                LedgerEntry::create([
                    'entry_date' => $order->delivery_date ?? $order->order_date,
                    'account_id' => $cash->id,
                    'debit' => $value,
                    'credit' => 0,
                    'reference_type' => 'sale',
                    'reference_id' => $order->id,
                    'description' => "Sale to {$order->customer->name} (Order #{$order->id})",
                ]);
                LedgerEntry::create([
                    'entry_date' => $order->delivery_date ?? $order->order_date,
                    'account_id' => $revenue->id,
                    'debit' => 0,
                    'credit' => $value,
                    'reference_type' => 'sale',
                    'reference_id' => $order->id,
                    'description' => "Sale to {$order->customer->name} (Order #{$order->id})",
                ]);
                $posted++;
            }
        });

        return redirect()->route('finance.index')
            ->with('success', "Posted {$posted} transaction(s) to the ledger.");
    }

    private function unpostedAllocationCount(): int
    {
        return CostAllocation::whereNotExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('ledger_entries')
              ->where('reference_type', 'cost_allocation')
              ->whereColumn('reference_id', 'cost_allocations.id');
        })->count();
    }

    private function account(string $code, string $name, string $type): ChartOfAccount
    {
        return ChartOfAccount::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type]);
    }
}
