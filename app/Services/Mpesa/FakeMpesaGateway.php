<?php

namespace App\Services\Mpesa;

use Illuminate\Support\Str;

/**
 * Stands in for Safaricom's Daraja API so the B2C/C2B flow works end-to-end
 * without real credentials — bound by default (see AppServiceProvider) until
 * MPESA_DRIVER=daraja is set.
 *
 * Resolves every disbursement as an immediate success. That is deliberately
 * unrealistic — real B2C can fail (insufficient float, wrong number, timeout)
 * — but modelling those failure paths convincingly needs real Daraja
 * responses to work from, which is exactly the part a real integration
 * brings. This class exists to make the rest of the app (ledger posting,
 * vouchers, receipts, the transaction log) demonstrably correct today.
 */
class FakeMpesaGateway implements MpesaGatewayContract
{
    public function initiateB2C(string $phone, float $amount, string $accountReference, string $remarks): array
    {
        return [
            'status' => 'success',
            'conversation_id' => 'AG_'.now()->format('Ymd').'_'.Str::upper(Str::random(12)),
            'originator_conversation_id' => (string) Str::uuid(),
            'mpesa_receipt_number' => $this->fakeReceiptNumber(),
            'result_description' => 'The service request has been accepted successfully. (simulated)',
        ];
    }

    /**
     * Shaped like a real Safaricom receipt (e.g. SGH4KLM9XZ): two letters,
     * then eight uppercase alphanumerics — so demo data and screenshots read
     * as genuine rather than obviously fake.
     */
    public function fakeReceiptNumber(): string
    {
        $letters = Str::upper(Str::random(2));
        $rest = Str::upper(Str::random(8));

        return $letters.$rest;
    }
}
