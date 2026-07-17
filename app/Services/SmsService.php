<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends SMS through the Bonga SMS HTTP gateway.
 *
 * Credentials live in config/services.php (`bonga_sms`), fed from the
 * BONGA_SMS_* / SMS_* env vars. The gateway is only reachable when those are
 * set, so every public method degrades gracefully: if SMS isn't configured, or
 * the request fails, sending is logged and skipped rather than throwing — a
 * texting failure must never block the password change that triggered it.
 */
class SmsService
{
    public function __construct(
        private ?string $key = null,
        private ?string $secret = null,
        private ?string $url = null,
        private ?string $clientId = null,
    ) {
        $this->key = $key ?? config('services.bonga_sms.key');
        $this->secret = $secret ?? config('services.bonga_sms.secret');
        $this->url = $url ?? config('services.bonga_sms.url');
        $this->clientId = $clientId ?? config('services.bonga_sms.client_id');
    }

    /** Whether the gateway has enough configuration to attempt a send. */
    public function isConfigured(): bool
    {
        return ! empty($this->key) && ! empty($this->secret) && ! empty($this->url);
    }

    /**
     * Send an SMS. Returns true on a successful gateway response, false if
     * skipped (not configured / no phone) or if the gateway rejected it.
     * Never throws — callers treat delivery as best-effort.
     */
    public function send(?string $phone, string $message): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('SMS not sent: Bonga SMS is not configured.');

            return false;
        }

        $msisdn = self::normalizePhone($phone);

        if ($msisdn === null) {
            Log::warning('SMS not sent: missing or invalid phone number.', ['phone' => $phone]);

            return false;
        }

        try {
            // Bonga SMS expects a form-urlencoded POST with these exact field
            // names (matches the osenco/bongasms SDK's /api/send-sms-v1 call);
            // a JSON body makes the gateway 500. serviceID defaults to 1.
            $payload = [
                'apiClientID' => $this->clientId,
                'key' => $this->key,
                'secret' => $this->secret,
                'txtMessage' => $message,
                'MSISDN' => $msisdn,
                'serviceID' => config('services.bonga_sms.service_id', 1),
            ];

            // NB: no ->acceptJson() — the gateway 406s on an Accept:
            // application/json header, even though it responds with JSON.
            $response = Http::asForm()
                ->timeout((int) config('services.bonga_sms.timeout', 20))
                ->post($this->url, $payload);

            // The gateway answers HTTP 200 even on failure, signalling the
            // outcome in the JSON body: status 222 == queued/sent, 666 == error
            // (e.g. "wrong api credentials"). Treat anything but 222 as failed.
            $status = $response->json('status');
            $ok = ! $response->failed() && (string) $status === '222';

            if (! $ok) {
                Log::error('SMS send failed.', [
                    'recipient' => $msisdn,
                    'http_status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            Log::info('SMS sent.', ['recipient' => $msisdn, 'response' => $response->body()]);

            return true;
        } catch (\Throwable $e) {
            Log::error('SMS send threw an exception.', [
                'recipient' => $msisdn,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Normalize a Kenyan phone number to MSISDN form (2547XXXXXXXX /
     * 2541XXXXXXXX). Accepts 07…, 01…, +254…, 254…, or a bare 7…/1…. Returns
     * null when the input can't be made into a plausible 12-digit MSISDN.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        // Strip everything but digits (drops +, spaces, dashes, parentheses).
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            // 07XXXXXXXX / 01XXXXXXXX -> 2547XXXXXXXX / 2541XXXXXXXX
            $digits = '254' . substr($digits, 1);
        } elseif (str_starts_with($digits, '254')) {
            // already in MSISDN form
        } elseif (strlen($digits) === 9 && (str_starts_with($digits, '7') || str_starts_with($digits, '1'))) {
            // bare 7XXXXXXXX / 1XXXXXXXX
            $digits = '254' . $digits;
        }

        // A valid Kenyan MSISDN is 254 followed by 9 digits.
        return preg_match('/^254[17]\d{8}$/', $digits) === 1 ? $digits : null;
    }
}
