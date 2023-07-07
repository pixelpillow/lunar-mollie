<?php

namespace Pixelpillow\LunarMollie;

use Illuminate\Support\ServiceProvider;
use Lunar\Facades\Payments;
use Pixelpillow\LunarMollie\Managers\MollieManager;

class MolliePaymentsServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/mollie.php', 'lunar.mollie');
    }

    public function boot()
    {
        // Register our payment type.
        Payments::extend('mollie', function ($app) {
            return $app->make(MolliePaymentType::class);
        });

        $this->app->singleton('gc:mollie', function ($app) {
            return $app->make(MollieManager::class);
        });

        $this->publishes([
            __DIR__.'/../config/mollie.php' => config_path('lunar/mollie.php'),
        ], 'lunar.mollie');
    }
}
