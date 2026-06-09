<?php

declare(strict_types=1);

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

    // WhatsApp provider selector + per-provider config (M8). The channel is
    // provider-agnostic; set `whatsapp.provider` to choose the transport.
    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'twilio'), // twilio | meta
    ],

    // Twilio: `whatsapp_content_sid` is an approved Content template (single
    // {{1}} variable) for production business-initiated messages.
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'whatsapp_content_sid' => env('TWILIO_WHATSAPP_CONTENT_SID'),
    ],

    // Meta WhatsApp Cloud API (direct, no BSP). `template` is the approved
    // template name (single {{1}} body variable); leave blank for free-text
    // (test number / 24h session) sending.
    'meta_whatsapp' => [
        'token' => env('META_WHATSAPP_TOKEN'),
        'phone_id' => env('META_WHATSAPP_PHONE_ID'),
        'template' => env('META_WHATSAPP_TEMPLATE'),
        'language' => env('META_WHATSAPP_LANG', 'en'),
    ],

];
