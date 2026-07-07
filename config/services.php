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

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'line' => [
        'client_id' => env('LINE_CLIENT_ID'),
        'client_secret' => env('LINE_CLIENT_SECRET'),
        'redirect' => env('LINE_REDIRECT_URI'),
        'liff_id' => env('LINE_LIFF_ID'),
        'messaging_channel_access_token' => env('LINE_MESSAGING_CHANNEL_ACCESS_TOKEN'),
        'messaging_channel_secret' => env('LINE_MESSAGING_CHANNEL_SECRET'),
        'official_account_url' => env('LINE_OFFICIAL_ACCOUNT_URL'),
    ],

    'instagram' => [
        'client_id' => env('INSTAGRAM_CLIENT_ID'),
        'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
        'redirect' => env('INSTAGRAM_REDIRECT_URI'),
    ],

    'crm' => [
        'base_url' => env('CRM_API_URL'),
        'token' => env('CRM_API_TOKEN'),
        'webhook_token' => env('CRM_WEBHOOK_TOKEN'),
    ],

    'beam' => [
        'merchant_id' => env('BEAM_MERCHANT_ID'),
        'api_key' => env('BEAM_API_KEY'),
        'webhook_key' => env('BEAM_WEBHOOK_KEY'),
        'environment' => env('BEAM_ENVIRONMENT', 'playground'),
        'default_fee_percent' => env('BEAM_DEFAULT_FEE_PERCENT', 3.0),
    ],

];
