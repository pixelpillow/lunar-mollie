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
    | Redirect URL after payment success generator
    |--------------------------------------------------------------------------
    |
    | This generator is used to generate the redirect URL after a successful
    | payment. This generator is instantiated with the current Lunar Cart and
    | Transaction.
    |
    */
    'redirect_url_generator' => \Pixelpillow\LunarMollie\Generators\RedirectOnSuccessUrlGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Webhook URL generator
    |--------------------------------------------------------------------------
    |
    | This generator is used to generate the webhook URL. This generator is instantiated
    | with the current Lunar Cart and Transaction.
    |
    | The generator should extend the BaseWebhookGenerator class.
    |
    */
    'webhook_url_generator' => \Pixelpillow\LunarMollie\Generators\WebhookUrlGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Test client adaptor
    |--------------------------------------------------------------------------
    |
    | This is the test client that is used for testing. To fake http requests
    | you can use the MollieLaravelHttpClientAdapter class from the
    | "mollie/laravel-mollie" package.
    |
    */
    'test_client_adaptor' => \Mollie\Laravel\MollieLaravelHttpClientAdapter::class,
];
