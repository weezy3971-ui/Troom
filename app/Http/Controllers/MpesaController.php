<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\MpesaTransaction;
use App\Models\SalesOrder;
use App\Services\Mpesa\FakeMpesaGateway;
use App\Services\Mpesa\MpesaGatewayContract;
use App\Services\SmsService;
use App\Support\C2BAllocation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The web-facing (authenticated) side of M-Pesa: disbursing to a vendor,
 * and the demo form that stands in for a customer paying at the paybill.
 * The real, unauthenticated Safaricom callbacks live in
 * MpesaWebhookController (routes/api.php) — see that class for why the two
 * are split.
 */
class MpesaController extends Controller
{
    public function __construct(private readonly MpesaGatewayContract $gateway)
    {
    }

    /** Every M-Pesa movement, in or out — the reconciliation log. */
    public function index(Request $request)
    {
        $query = MpesaTransaction::with('payable', 'initiatedBy')->latest('id');

        if ($direction = $request->input('direction')) {
            $query->where('direction', $direction);
        }

        $transactions = $query->get();
        $unallocated = MpesaTransaction::where('direction', 'c2b')
            ->where('status', 'success')
            ->whereNull('payable_type')
            ->count();

        return view('mpesa.index', compact('transactions', 'direction', 'unallocated'));
    }

    /**
     * Pay a vendor via B2C. Requires a vendor with a usable phone number and
     * an expense that hasn't already been disbursed — an expense logged as
     * "cash" or already disbursed once has nothing for this to do.
     */
    public function disburse(Expense $expense)
    {
        $expense->load('vendor');

        if (! $expense->vendor?->isPayable()) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'This vendor has no M-Pesa number on file, or is inactive — add one before disbursing.');
        }

        if ($expense->isDisbursed()) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'This expense has already been disbursed by M-Pesa.');
        }

        $reference = $expense->voucher_number ?: 'EXP-'.str_pad((string) $expense->id, 6, '0', STR_PAD_LEFT);

        $result = $this->gateway->initiateB2C(
            $expense->vendor->phone,
            (float) $expense->amount,
            $reference,
            "Payment for {$expense->description}"
        );

        $transaction = MpesaTransaction::create([
            'direction' => 'b2c',
            'phone' => $expense->vendor->phone,
            'amount' => $expense->amount,
            'account_reference' => $reference,
            'mpesa_receipt_number' => $result['mpesa_receipt_number'],
            'conversation_id' => $result['conversation_id'],
            'originator_conversation_id' => $result['originator_conversation_id'],
            'status' => $result['status'],
            'result_description' => $result['result_description'],
            'raw_payload' => $result,
            'payable_type' => Expense::class,
            'payable_id' => $expense->id,
            'initiated_by' => auth()->id(),
        ]);

        if ($transaction->isSuccess()) {
            $expense->update(['payment_mode' => 'mpesa']);
            $expense->issueVoucher();

            return redirect()->route('expenses.show', $expense)
                ->with('success', "Disbursed KES ".number_format((float) $expense->amount, 2)
                    ." to {$expense->vendor->name} — M-Pesa code {$transaction->mpesa_receipt_number}.");
        }

        // Real Daraja B2C returns 'pending' here and confirms later via
        // MpesaWebhookController::b2cResult() — this branch is what a real
        // integration hits; FakeMpesaGateway never returns anything but success.
        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Disbursement submitted — awaiting confirmation from M-Pesa.');
    }

    /**
     * Demo stand-in for a customer paying at the paybill menu with no app
     * involvement. Builds the same payload shape Safaricom's confirmation
     * webhook delivers and hands it to C2BAllocation — the one place that
     * logic lives, real callback or simulated.
     */
    public function simulateC2BForm()
    {
        $openInvoices = SalesOrder::with('customer')
            ->whereNotNull('invoice_number')
            ->get()
            ->filter(fn (SalesOrder $order) => $order->balanceDue() > 0);

        return view('mpesa.simulate-c2b', compact('openInvoices'));
    }

    public function simulateC2B(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:30',
            'amount' => 'required|numeric|min:1',
            'account_reference' => 'nullable|string|max:255',
        ]);

        $phone = SmsService::normalizePhone($validated['phone']);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'phone' => 'Enter a valid Kenyan mobile number — this simulates the number M-Pesa would report.',
            ]);
        }

        $transaction = C2BAllocation::receive([
            'phone' => $phone,
            'amount' => $validated['amount'],
            'account_reference' => $validated['account_reference'] ?? '',
            // This form exists specifically to simulate what a real C2B
            // confirmation would carry, independent of MPESA_DRIVER — so it
            // always fakes the receipt code itself rather than asking the
            // bound gateway for one.
            'mpesa_receipt_number' => (new FakeMpesaGateway)->fakeReceiptNumber(),
        ]);

        $message = $transaction->isUnallocated()
            ? "KES ".number_format((float) $transaction->amount, 2)." received but no invoice matched \"{$validated['account_reference']}\" — it's held for manual allocation below."
            : "KES ".number_format((float) $transaction->amount, 2)." received and matched to {$transaction->payable->invoice_number} — receipt issued.";

        return redirect()->route('mpesa.index')->with('success', $message);
    }
}
