<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\CostAllocation;
use App\Models\Expense;
use App\Models\Farm;
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

        return view('expenses.create', compact('farms', 'blocks', 'categories', 'paymentModes'));
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
        $expense->load('farm', 'block', 'logger');
        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $farms = Farm::orderBy('name')->get();
        $blocks = Block::with('farm')->orderBy('name')->get();
        $categories = Expense::CATEGORIES;
        $paymentModes = Expense::PAYMENT_MODES;

        return view('expenses.edit', compact('expense', 'farms', 'blocks', 'categories', 'paymentModes'));
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $this->validateExpense($request);

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $validated['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }
        unset($validated['receipt']);

        $expense->update($validated);
        $this->syncCostAllocation($expense);

        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        CostAllocation::where('source_type', 'expense')
            ->where('source_id', $expense->id)
            ->delete();

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted.');
    }

    private function validateExpense(Request $request): array
    {
        return $request->validate([
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => ['nullable', Rule::in(Expense::PAYMENT_MODES)],
            'expense_date' => 'required|date',
            'description' => 'required|string',
            'receipt' => 'nullable|image|max:5120',
            'farm_id' => 'nullable|exists:farms,id',
            'block_id' => 'nullable|exists:blocks,id',
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
