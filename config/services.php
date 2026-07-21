<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Shared bearer token the digital weighing scale uses to push readings to
    // the ingest endpoint. Endpoint is disabled while this is empty.
    'weigh_scale' => [
        'token' => env('WEIGH_SCALE_TOKEN'),
    ],

    'whatsapp' => [
        'test_mode' => (bool) env('WHATSAPP_TEST_MODE', true),
    ],

    /*
    | Anthropic (Claude) — powers AI-generated reports and the AI companion.
    | Set ANTHROPIC_API_KEY in your .env. The model defaults to Haiku 4.5, the
    | most cost-effective option (see docs/ai-companion-implementation-plan.md
    | §5) — switch to claude-sonnet-5 or claude-opus-4-8 via ANTHROPIC_MODEL.
    */
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 2000),
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 60),
    ],

    /*
    | Bonga SMS — used to text users their password when an admin sets or
    | resets it. SMS sending is skipped (silently, without failing the
    | password change) whenever the API key is not configured.
    */
    'bonga_sms' => [
        'key' => env('BONGA_SMS_API_KEY', env('SMS_API_KEY')),
        'secret' => env('BONGA_SMS_API_SECRET', env('SMS_API_SECRET')),
        'url' => env('BONGA_SMS_API_URL', env('SMS_API_URL')),
        'client_id' => env('SMS_API_CLIENT_ID'),
        'service_id' => env('SMS_SERVICE_ID', 1),
        'timeout' => (int) env('SMS_TIMEOUT', 20),
    ],

];
