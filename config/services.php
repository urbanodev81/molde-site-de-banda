<?php

return [

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

    'central' => [
        'base_url' => env('CENTRAL_BASE_URL'),
        'project_token' => env('CENTRAL_PROJECT_TOKEN'),
        'webhook_secret' => env('CENTRAL_WEBHOOK_SECRET'),
    ],

    'captcha' => [

        'driver' => env('CAPTCHA_DRIVER', 'altcha'),

        'hmac_key' => env('CAPTCHA_HMAC_KEY'),

        'cost' => (int) env('CAPTCHA_COST', 50_000),

        'qa_bypass_token' => env('CAPTCHA_QA_BYPASS_TOKEN'),
    ],

];
