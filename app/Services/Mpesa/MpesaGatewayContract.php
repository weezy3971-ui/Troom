<?php

namespace App\Services\Mpesa;

/**
 * What the app needs from an M-Pesa gateway, independent of which one is
 * behind it. Two implementations exist:
 *
 *   FakeMpesaGateway — the bound default. Simulates Safaricom's Daraja API
 *   synchronously so the whole B2C/C2B flow can be demoed and tested without
 *   real credentials, a public callback URL, or a Safaricom account.
 *
 *   DarajaGateway — a real client stub, structurally correct against
 *   Safaricom's published Daraja API, but not wired up. Filling in its three
 *   methods and setting MPESA_DRIVER=daraja in .env (with real credentials)
 *   is the entire integration step — nothing else in the app needs to change,
 *   because both classes speak this same contract.
 *
 * C2B has no "initiate" here on purpose: a C2B payment is customer-initiated
 * at the paybill, not something Trooms triggers. The app only ever *receives*
 * one, via MpesaController::c2bConfirmation() — see routes/api.php.
 */
interface MpesaGatewayContract
{
    /**
     * Disburse money to a phone number (Business to Customer).
     *
     * Real Daraja B2C is asynchronous: this call only confirms Safaricom
     * *accepted* the request: 'status' is 'pending' at return time, and the
     * SDK is expected to call MpesaController::b2cResult() with the eventual
     * success/failure. FakeMpesaGateway instead resolves synchronously and
     * calls that same result handler itself, which is why callers should
     * never assume 'status' is final just because this method returned.
     *
     * @return array{
     *     status: 'pending'|'success'|'failed',
     *     conversation_id: string,
     *     originator_conversation_id: string,
     *     mpesa_receipt_number: ?string,
     *     result_description: ?string,
     * }
     */
    public function initiateB2C(string $phone, float $amount, string $accountReference, string $remarks): array;
}
