<?php

namespace Pixelpillow\LunarMollie\Tests;

use Cartalyst\Converter\Laravel\ConverterServiceProvider;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\Facades\Config;
use Kalnoy\Nestedset\NestedSetServiceProvider;
use Lunar\Facades\Taxes;
use Lunar\LunarServiceProvider;
use Lunar\Tests\Stubs\User;
use Mollie\Api\MollieApiClient;
use Mollie\Laravel\MollieLaravelHttpClientAdapter;
use Pixelpillow\LunarMollie\Managers\MollieManager;
use Pixelpillow\LunarMollie\MolliePaymentsServiceProvider;
use Pixelpillow\LunarMollie\Tests\Stubs\Lunar\TestTaxDriver;
use Pixelpillow\LunarMollie\Tests\Stubs\TestRedirectUrlGenerator;
use Pixelpillow\LunarMollie\Tests\Stubs\TestWebhookUrlGenerator;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\LaravelBlink\BlinkServiceProvider;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @var MollieManager
     */
    public $mollieManager;

    /**
     * @var MollieApiClient
     */
    public $mollieApiClient;

    public function setUp(): void
    {
        parent::setUp();
        // additional setup
        Config::set('providers.users.model', User::class);
        Config::set('lunar.mollie.api_key', 'test_G3ys6guxc9Su7VJ2xctR4N4VqvGbQR');
        Config::set('lunar.mollie.webhook_url_generator', TestWebhookUrlGenerator::class);
        Config::set('lunar.mollie.redirect_url_generator', TestRedirectUrlGenerator::class);

        Config::set('taxes.driver', TestTaxDriver::class);

        Config::set('lunar.taxes.driver', 'test');

        Taxes::extend('test', function ($app) {
            return $app->make(TestTaxDriver::class);
        });

        activity()->disableLogging();

        // Setup Mollie API
        $this->mollieManager = new MollieManager(new MollieLaravelHttpClientAdapter());
        $this->mollieApiClient = app(MollieApiClient::class);

    }

    protected function getPackageProviders($app)
    {
        return [
            LunarServiceProvider::class,
            MolliePaymentsServiceProvider::class,
            MediaLibraryServiceProvider::class,
            ActivitylogServiceProvider::class,
            ConverterServiceProvider::class,
            BlinkServiceProvider::class,
            NestedSetServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->useEnvironmentPath(__DIR__.'/..');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);

        Config::set('lunar.mollie', require __DIR__.'/../config/mollie.php');

        /**
         * App configuration
         */
        Config::set('database.default', 'sqlite');

        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
    }
}
