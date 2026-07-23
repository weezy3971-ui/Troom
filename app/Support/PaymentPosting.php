<?php

namespace App\Support;

use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use App\Models\Payment;

/**
 * Posts received payments to the native ledger.
 *
 * Note how this splits from FinanceController's batch posting. That routine
 * recognises a fulfilled order as Dr Cash / Cr Revenue in one step — fine while
 * nothing tracked payment, but it assumes every delivered order was also
 * collected. Now that payments are real, the two halves separate:
 *
 *   fulfilment  Dr Accounts Receivable   Cr Produce Sales   (revenue earned)
 *   payment     Dr Cash / M-Pesa Float   Cr Accounts Receivable  (cash collected)
 *
 * Posting both without that split would count every sale twice.
 */
class PaymentPosting
{
    /** Where the money landed, by payment method. */
    private const DESTINATION_ACCOUNTS = [
        'mpesa' => ['1100', 'M-Pesa Float', 'asset'],
        'cash' => ['1000', 'Cash & Bank', 'asset'],
        'bank_transfer' => ['1000', 'Cash & Bank', 'asset'],
        'cheque' => ['1000', 'Cash & Bank', 'asset'],
        'other' => ['1000', 'Cash & Bank', 'asset'],
    ];

    public static function post(Payment $payment): void
    {
        [$code, $name, $type] = self::DESTINATION_ACCOUNTS[$payment->method]
            ?? self::DESTINATION_ACCOUNTS['other'];

        $destination = self::account($code, $name, $type);
        $receivable = self::account('1200', 'Accounts Receivable', 'asset');

        $description = self::describe($payment);

        self::entry($payment, $destination->id, (float) $payment->amount, 0, $description);
        self::entry($payment, $receivable->id, 0, (float) $payment->amount, $description);
    }

    /**
     * A voided payment is reversed with an equal and opposite pair rather than
     * by deleting the original entries — the ledger keeps the full story of
     * what was recorded and what undid it.
     */
    public static function reverse(Payment $payment): void
    {
        [$code, $name, $type] = self::DESTINATION_ACCOUNTS[$payment->method]
            ?? self::DESTINATION_ACCOUNTS['other'];

        $destination = self::account($code, $name, $type);
        $receivable = self::account('1200', 'Accounts Receivable', 'asset');

        $description = 'VOID — '.self::describe($payment);

        self::entry($payment, $destination->id, 0, (float) $payment->amount, $description);
        self::entry($payment, $receivable->id, (float) $payment->amount, 0, $description);
    }

    private static function entry(Payment $payment, int $accountId, float $debit, float $credit, string $description): void
    {
        LedgerEntry::create([
            'entry_date' => $payment->paid_at,
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
            'reference_type' => 'payment',
            'reference_id' => $payment->id,
            'description' => $description,
        ]);
    }

    private static function describe(Payment $payment): string
    {
        $who = $payment->customer?->name ?? 'walk-in';

        return "Payment {$payment->receipt_number} from {$who} ({$payment->methodLabel()})";
    }

    private static function account(string $code, string $name, string $type): ChartOfAccount
    {
        return ChartOfAccount::firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => $type]
        );
    }
}
