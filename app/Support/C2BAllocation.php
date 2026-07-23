<?php

namespace App\Support;

use App\Models\MpesaTransaction;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

/**
 * What happens when a C2B payment arrives at the paybill, whether that is a
 * real Safaricom confirmation callback or the demo "simulate" form — both
 * call this, so there is exactly one implementation of the matching rule to
 * keep correct.
 *
 * Safaricom's C2B has no app-initiated prompt: a customer pays at the paybill
 * menu and types whatever they like as the account reference. Matching is
 * therefore best-effort, against the invoice number — a mistyped or missing
 * reference is expected, not an error, and is left for manual allocation
 * rather than rejected.
 */
class C2BAllocation
{
    public static function receive(array $payload): MpesaTransaction
    {
        $accountReference = trim((string) ($payload['account_reference'] ?? ''));

        $order = $accountReference !== ''
            ? SalesOrder::where('invoice_number', $accountReference)->first()
            : null;

        return DB::transaction(function () use ($payload, $accountReference, $order) {
            $transaction = MpesaTransaction::create([
                'direction' => 'c2b',
                'phone' => $payload['phone'],
                'amount' => $payload['amount'],
                'account_reference' => $accountReference ?: null,
                'mpesa_receipt_number' => $payload['mpesa_receipt_number'],
                'status' => 'success',
                'result_description' => $order
                    ? "Matched to invoice {$accountReference}."
                    : 'No matching invoice — held for manual allocation.',
                'raw_payload' => $payload,
                'payable_type' => $order ? SalesOrder::class : null,
                'payable_id' => $order?->id,
            ]);

            if ($order) {
                self::settle($order, $transaction);
            }

            return $transaction;
        });
    }

    private static function settle(SalesOrder $order, MpesaTransaction $transaction): void
    {
        if (! $order->isInvoiced()) {
            $order->issueInvoice();
        }

        // A C2B amount that overshoots the balance due (or arrives after the
        // order is already settled — a customer paying twice at the paybill
        // menu) is exactly what a mistyped reference produces. It is banked
        // against the order anyway rather than silently dropped: Finance can
        // see the overpayment on the order and decide, but the money's trail
        // is never lost. This is why C2B goes straight to Payment::create()
        // rather than through PaymentController::store(), which would reject it.
        $payment = ActivityLogger::as('payment_received', fn () => $order->payments()->create([
            'customer_id' => $order->customer_id,
            'method' => 'mpesa',
            'amount' => $transaction->amount,
            'paid_at' => now()->toDateString(),
            'reference' => $transaction->mpesa_receipt_number,
            'payer_phone' => $transaction->phone,
            'notes' => 'Received via M-Pesa C2B.',
        ]));

        $order->refreshPaymentStatus();
        PaymentPosting::post($payment);

        $transaction->update(['payable_id' => $order->id, 'payable_type' => SalesOrder::class]);
    }
}
