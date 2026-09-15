<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\MpesaTransaction;
use App\Support\C2BAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The four URLs a real Daraja integration registers with Safaricom. Stateless
 * and unauthenticated by design — routes/api.php carries no session/CSRF
 * middleware, and Safaricom cannot present a login or a CSRF token — so every
 * handler here treats its payload as untrusted input and is written to be
 * safely retried (Safaricom does retry callbacks on timeout).
 *
 * None of these fire in the demo: FakeMpesaGateway resolves B2C synchronously
 * and MpesaController::simulateC2B() calls C2BAllocation directly. They exist
 * so that plugging in DarajaGateway needs no new routes or business logic —
 * only real credentials and these four URLs handed to Safaricom.
 */
class MpesaWebhookController extends Controller
{
    /**
     * Safaricom asks before completing a C2B payment whether to accept it.
     * Always accepted — the alternative is bouncing a customer's payment at
     * the paybill menu, which is a support problem, not a validation rule
     * worth enforcing here.
     */
    public function c2bValidation(Request $request)
    {
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /** Fires once a C2B payment has actually completed. */
    public function c2bConfirmation(Request $request)
    {
        // Only what is needed to trace the payment. The full payload carries
        // the payer's phone number and name, which must not reach the logs.
        Log::info('M-Pesa C2B confirmation received', [
            'trans_id' => $request->input('TransID'),
            'bill_ref' => $request->input('BillRefNumber'),
            'amount' => $request->input('TransAmount'),
            'msisdn' => self::maskMsisdn($request->input('MSISDN')),
        ]);

        C2BAllocation::receive([
            'phone' => $request->input('MSISDN'),
            'amount' => $request->input('TransAmount'),
            'account_reference' => $request->input('BillRefNumber'),
            'mpesa_receipt_number' => $request->input('TransID'),
        ]);

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /** Fires once a B2C disbursement (initiated via MpesaController::disburse()) resolves. */
    public function b2cResult(Request $request)
    {
        Log::info('M-Pesa B2C result received', [
            'conversation_id' => $request->input('Result.ConversationID'),
            'result_code' => $request->input('Result.ResultCode'),
        ]);

        $result = $request->input('Result', []);
        $conversationId = $result['ConversationID'] ?? null;

        $transaction = MpesaTransaction::where('conversation_id', $conversationId)->first();

        // Idempotent: Safaricom may retry this callback, and a transaction
        // already resolved (or one we have no record of) is simply acknowledged.
        if (! $transaction || $transaction->status !== 'pending') {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $succeeded = (int) ($result['ResultCode'] ?? 1) === 0;

        $receiptNumber = collect($result['ResultParameters']['ResultParameter'] ?? [])
            ->firstWhere('Key', 'TransactionReceipt')['Value'] ?? null;

        $transaction->update([
            'status' => $succeeded ? 'success' : 'failed',
            'mpesa_receipt_number' => $receiptNumber,
            'result_description' => $result['ResultDesc'] ?? null,
            'raw_payload' => $request->all(),
        ]);

        if ($succeeded && $transaction->payable_type === Expense::class) {
            $expense = $transaction->payable;
            $expense->update(['payment_mode' => 'mpesa']);
            $expense->issueVoucher();
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /** Fires if a B2C request times out in Safaricom's queue rather than resolving. */
    public function b2cTimeout(Request $request)
    {
        Log::warning('M-Pesa B2C timeout received', [
            'conversation_id' => $request->input('Result.ConversationID'),
        ]);

        $conversationId = $request->input('Result.ConversationID');

        MpesaTransaction::where('conversation_id', $conversationId)
            ->where('status', 'pending')
            ->update(['status' => 'failed', 'result_description' => 'Timed out in the Safaricom queue.']);

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Keep enough of a phone number to trace a payment, not enough to identify
     * the payer. Safaricom sends the full MSISDN; logs keep only its tail.
     */
    private static function maskMsisdn(mixed $msisdn): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $msisdn);

        if ($digits === null || $digits === '') {
            return null;
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
