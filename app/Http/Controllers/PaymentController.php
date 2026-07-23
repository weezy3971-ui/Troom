<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesOrder;
use App\Support\ActivityLogger;
use App\Support\PaymentPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Receiving money against a sales order, and the receipt that comes with it.
 *
 * There is deliberately no update route: a receipt in a customer's hands must
 * keep meaning what it said, so a wrong payment is voided and re-recorded
 * rather than edited.
 */
class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('customer', 'receivedBy', 'payable')->latest('paid_at')->latest('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($method = $request->input('method')) {
            $query->where('method', $method);
        }

        $payments = $query->get();
        $received = (float) Payment::active()->sum('amount');

        return view('payments.index', compact('payments', 'search', 'received'));
    }

    /**
     * Record money received against an order. The order is invoiced on the
     * spot if it has not been already — in practice the first payment is often
     * what prompts anyone to raise the invoice at all.
     */
    public function store(Request $request, SalesOrder $salesOrder)
    {
        $validated = $request->validate([
            'method' => 'required|in:'.implode(',', Payment::METHODS),
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'payer_phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:1000',
        ]);

        // An M-Pesa payment without its transaction code cannot be reconciled
        // against the statement later, which is the whole point of recording it.
        if ($validated['method'] === 'mpesa' && empty($validated['reference'])) {
            throw ValidationException::withMessages([
                'reference' => 'Enter the M-Pesa transaction code — without it this payment cannot be reconciled.',
            ]);
        }

        if (! $salesOrder->isInvoiced()) {
            $salesOrder->issueInvoice();
        }

        if ($salesOrder->balanceDue() <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'This order is already paid in full.',
            ]);
        }

        // Overpaying is almost always a typo, and it is far cheaper to catch
        // here than to unpick from the ledger afterwards.
        if (round((float) $validated['amount'], 2) > round($salesOrder->balanceDue(), 2)) {
            throw ValidationException::withMessages([
                'amount' => 'That is more than the KES '.number_format($salesOrder->balanceDue(), 2)
                    .' still outstanding on this order.',
            ]);
        }

        $payment = DB::transaction(function () use ($salesOrder, $validated) {
            $payment = ActivityLogger::as('payment_received', fn () => $salesOrder->payments()->create([
                ...$validated,
                'customer_id' => $salesOrder->customer_id,
                'received_by' => auth()->id(),
            ]));

            $salesOrder->refreshPaymentStatus();
            PaymentPosting::post($payment);

            return $payment;
        });

        return redirect()->route('payments.receipt', $payment)
            ->with('success', "Payment recorded. Receipt {$payment->receipt_number} issued.");
    }

    /** The customer-facing receipt — printed, or saved as PDF from the browser. */
    public function receipt(Payment $payment)
    {
        $payment->load('customer', 'receivedBy', 'voidedBy', 'payable');

        // The receipt shows the outstanding balance, which only a sales order
        // can answer. Other payable types (horse rides today) simply omit it.
        $order = $payment->payable instanceof SalesOrder ? $payment->payable : null;
        $order?->loadMissing('crop');

        return view('payments.receipt', compact('payment', 'order'));
    }

    /**
     * Reverse a payment recorded in error. The row and its receipt number stay
     * put so the series has no gaps, the ledger gets a mirror-image entry
     * rather than a deletion, and the order's balance falls back out of the
     * remaining payments.
     */
    public function void(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'void_reason' => 'required|string|max:255',
        ]);

        if ($payment->isVoided()) {
            return redirect()->route('payments.receipt', $payment)
                ->with('success', 'That payment was already voided.');
        }

        DB::transaction(function () use ($payment, $validated) {
            $payment->forceFill([
                'voided_at' => now(),
                'voided_by' => auth()->id(),
                'void_reason' => $validated['void_reason'],
            ])->save();

            PaymentPosting::reverse($payment);

            if ($payment->payable instanceof SalesOrder) {
                $payment->payable->refreshPaymentStatus();
            }
        });

        return redirect()->route('payments.receipt', $payment)
            ->with('success', "Receipt {$payment->receipt_number} voided.");
    }
}
