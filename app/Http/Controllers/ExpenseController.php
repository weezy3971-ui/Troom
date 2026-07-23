<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\CostAllocation;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\LandPreparation;
use App\Models\Vendor;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('farm', 'block', 'logger')->latest('expense_date')->latest('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('farm', fn($f) => $f->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('logger', fn($l) => $l->where('name', 'like', "%{$search}%"));
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $expenses = $query->get();
        $total = $expenses->sum('amount');
        $categories = Expense::CATEGORIES;

        return view('expenses.index', compact('expenses', 'search', 'category', 'total', 'categories'));
    }

    public function create()
    {
        $farms = Farm::orderBy('name')->get();
        $blocks = Block::with('farm')->orderBy('name')->get();
        $categories = Expense::CATEGORIES;
        $paymentModes = Expense::PAYMENT_MODES;
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $landPreparations = $this->openLandPreparations();

        return view('expenses.create', compact('farms', 'blocks', 'categories', 'paymentModes', 'vendors', 'landPreparations'));
    }

    /**
     * Preparation rounds a cost can still be booked against — anything not yet
     * closed out, newest first, so prep spend has somewhere to land.
     */
    private function openLandPreparations()
    {
        return LandPreparation::with('block.farm')
            ->whereIn('status', ['planned', 'in_progress'])
            ->orWhereNull('crop_cycle_id')
            ->latest('id')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $this->validateExpense($request);
        $validated['logged_by'] = $request->user()?->id;

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }
        unset($validated['receipt']);

        $expense = Expense::create($validated);
        $this->syncCostAllocation($expense);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense logged.');
    }

    public function show(Expense $expense)
    {
        $expense->load('farm', 'block', 'logger', 'vendor', 'mpesaTransactions');
        return view('expenses.show', compact('expense'));
    }

    /** Issue Trooms's proof that the vendor was paid — the outbound receipt. */
    public function issueVoucher(Expense $expense)
    {
        if (! $expense->vendor_id) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'Add a vendor to this expense before issuing a payment voucher — a voucher has to say who was paid.');
        }

        $expense->issueVoucher();

        return redirect()->route('expenses.voucher', $expense);
    }

    /** The printable voucher itself. */
    public function voucher(Expense $expense)
    {
        if (! $expense->isVouchered()) {
            abort(404);
        }

        $expense->load('vendor', 'farm', 'block', 'logger');

        return view('expenses.voucher', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        if ($expense->isLocked()) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'This expense was logged more than a day ago and can no longer be edited.');
        }

        $farms = Farm::orderBy('name')->get();
        $blocks = Block::with('farm')->orderBy('name')->get();
        $categories = Expense::CATEGORIES;
        $paymentModes = Expense::PAYMENT_MODES;
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $landPreparations = $this->openLandPreparations();

        return view('expenses.edit', compact('expense', 'farms', 'blocks', 'categories', 'paymentModes', 'vendors', 'landPreparations'));
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->isLocked()) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'This expense was logged more than a day ago and can no longer be edited.');
        }

        $before = $expense->only(['category', 'amount', 'payment_mode', 'expense_date', 'description', 'farm_id', 'block_id']);

        $validated = $this->validateExpense($request);

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $validated['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }
        unset($validated['receipt']);

        // Suppress the generic "Updated Expense" audit entry in favour of an
        // explicit before/after description below — expense edits are
        // sensitive enough to spell out exactly what changed and by whom.
        Expense::withoutEvents(fn () => $expense->update($validated));

        $this->syncCostAllocation($expense);
        $this->logFieldChanges($request, $expense, $before);

        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Expense updated.');
    }

    public function destroy(Request $request, Expense $expense)
    {
        if ($expense->isLocked()) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'This expense was logged more than a day ago and can no longer be deleted.');
        }

        CostAllocation::where('source_type', 'expense')
            ->where('source_id', $expense->id)
            ->delete();

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $summary = ucfirst(str_replace('_', ' ', $expense->category)) . ' — KES ' . number_format($expense->amount, 2)
            . ' (' . \Illuminate\Support\Str::limit($expense->description, 60) . ')';

        Expense::withoutEvents(fn () => $expense->delete());

        ActivityLogger::log(
            'deleted',
            null,
            "{$request->user()->name} deleted an expense: {$summary}"
        );

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted.');
    }

    /**
     * Log exactly what changed on an expense edit, e.g. "James changed the
     * amount from 2,000.00 to 1,000.00" — plain before/after values rather
     * than a generic "updated" entry, since expenses are sensitive enough
     * that admins need to see precisely what was altered.
     */
    private function logFieldChanges(Request $request, Expense $expense, array $before): void
    {
        $labels = [
            'category' => 'category',
            'amount' => 'amount',
            'payment_mode' => 'payment mode',
            'expense_date' => 'date',
            'description' => 'description',
            'farm_id' => 'farm',
            'block_id' => 'block',
        ];

        $changes = [];
        foreach ($before as $field => $old) {
            $new = $expense->{$field};
            if ((string) $old === (string) $new) {
                continue;
            }

            $format = fn ($v) => $field === 'amount' ? number_format((float) $v, 2) : ($v ?? '—');
            $changes[] = "{$labels[$field]} from {$format($old)} to {$format($new)}";
        }

        if (empty($changes)) {
            return;
        }

        ActivityLogger::log(
            'updated',
            $expense,
            "{$request->user()->name} changed the " . implode(', ', $changes) . ' on this expense'
        );
    }

    private function validateExpense(Request $request): array
    {
        return $request->validate([
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'vendor_id' => 'nullable|exists:vendors,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => ['nullable', Rule::in(Expense::PAYMENT_MODES)],
            'expense_date' => 'required|date',
            'description' => 'required|string',
            'receipt' => 'nullable|image|max:5120',
            'farm_id' => 'nullable|exists:farms,id',
            'block_id' => 'nullable|exists:blocks,id',
            'land_preparation_id' => 'nullable|exists:land_preparations,id',
        ]);
    }

    /**
     * Business rule: every expense generates a Cost Allocation row (source
     * type: expense) so ad-hoc field spend rolls into the same cost/budget
     * totals as fertigation, labour, etc.
     */
    private function syncCostAllocation(Expense $expense): void
    {
        CostAllocation::updateOrCreate(
            ['source_type' => 'expense', 'source_id' => $expense->id],
            [
                'block_id' => $expense->block_id,
                'amount' => $expense->amount,
                'allocation_date' => $expense->expense_date,
                'description' => ucfirst(str_replace('_', ' ', $expense->category)) . ': ' . $expense->description,
            ]
        );
    }
}
