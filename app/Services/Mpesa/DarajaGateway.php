<?php

namespace App\Services\Mpesa;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The real integration point. Not wired up — every method throws until
 * someone with live Daraja credentials fills this in. That is the entire
 * remaining step to go from demo to production: nothing else in the app
 * (controllers, views, ledger posting, vouchers) needs to change, because
 * this class speaks the same MpesaGatewayContract as FakeMpesaGateway.
 *
 * To finish this class:
 *   1. Register for a Daraja app at https://developer.safaricom.co.ke and
 *      get a consumer key/secret, an initiator name + password, the
 *      certificate for encrypting the initiator password (sandbox cert is
 *      published by Safaricom; production cert comes from your shortcode
 *      paperwork), and a confirmed paybill/till shortcode.
 *   2. Fill in MPESA_* in .env — see .env.example for the full list.
 *   3. Implement getAccessToken() (OAuth, cached ~55 min) and initiateB2C()
 *      below against the endpoints noted inline.
 *   4. Register MpesaController::c2bConfirmation()/c2bValidation() as your
 *      C2B Confirmation/Validation URLs, and b2cResult()/b2cTimeout() as
 *      your B2C ResultURL/QueueTimeOutURL, with Safaricom (routes/api.php
 *      already exposes all four).
 *   5. Set MPESA_DRIVER=daraja — AppServiceProvider binds this class instead
 *      of FakeMpesaGateway from that point on.
 */
class DarajaGateway implements MpesaGatewayContract
{
    public function __construct(
        private readonly ?string $consumerKey = null,
        private readonly ?string $consumerSecret = null,
        private readonly ?string $baseUrl = null,
    ) {
    }

    public function initiateB2C(string $phone, float $amount, string $accountReference, string $remarks): array
    {
        // POST {baseUrl}/mpesa/b2c/v1/paymentrequest — see
        // https://developer.safaricom.co.ke/APIs/BusinessToCustomer
        // Needs: InitiatorName, SecurityCredential (initiator password
        // RSA-encrypted with Safaricom's public certificate), CommandID
        // (BusinessPayment), Amount, PartyA (shortcode), PartyB (phone),
        // Remarks, QueueTimeOutURL, ResultURL.
        throw new \RuntimeException(
            'DarajaGateway is not configured. Set MPESA_DRIVER=daraja and real '
            .'credentials in .env, then implement DarajaGateway::initiateB2C() '
            .'— see the class docblock for the integration steps.'
        );
    }

    /** OAuth token, cached just under its ~1hr expiry. Not yet implemented. */
    private function getAccessToken(): string
    {
        return Cache::remember('daraja_access_token', 3000, function () {
            // GET {baseUrl}/oauth/v1/generate?grant_type=client_credentials
            // with Basic Auth (consumerKey:consumerSecret).
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get("{$this->baseUrl}/oauth/v1/generate", ['grant_type' => 'client_credentials']);

            return $response->json('access_token');
        });
    }
}
