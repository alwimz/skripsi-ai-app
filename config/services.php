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

    'groq' => [
        'key' => env('GROQ_API_KEY'),
    ],

    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),
    ],

    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
    ],

    'serper' => [
        'key' => env('SERPER_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'payment_gateway' => env('PAYMENT_GATEWAY', 'auto'),

    'mayar' => [
        'api_key' => env('MAYAR_API_KEY'),
        'webhook_token' => env('MAYAR_WEBHOOK_TOKEN'),
        'base_url' => env('MAYAR_BASE_URL', 'https://api.mayar.id/hl/v1'),
    ],

    'pakasir' => [
        'project' => env('PAKASIR_PROJECT'),
        'api_key' => env('PAKASIR_API_KEY'),
        'base_url' => env('PAKASIR_BASE_URL', 'https://app.pakasir.com'),
    ],

    'meta' => [
        'pixel_id' => env('META_PIXEL_ID', '1487586500131939'),
        'capi_token' => env('META_CAPI_TOKEN', ''),
        'domain_verification' => env('META_DOMAIN_VERIFICATION', 'sacfg5assusezhylfsz3vatv9hh7vr'),
    ],
];
