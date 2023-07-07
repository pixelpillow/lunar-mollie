<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mollie API key
    |--------------------------------------------------------------------------
    |
    | The Mollie API key for your website. You can find it in your
    | Mollie dashboard. It starts with 'test_' or 'live_'.
    |
    */
    'api_key' => env('MOLLIE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Redirect URL
    |--------------------------------------------------------------------------
    |
    | The URL to redirect to after a successful payment.
    |
    */
    'redirect_url' => env('MOLLIE_REDIRECT_URL'),

    /*
    |--------------------------------------------------------------------------
    | Webhook URL
    |--------------------------------------------------------------------------
    |
    | The URL to send webhook events to.
    |
    */
    'webhook_url' => env('MOLLIE_WEBHOOK_URL'),
];
