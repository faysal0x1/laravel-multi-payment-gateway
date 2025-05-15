<?php

namespace faysal0x1\PaymentGateway;

use Illuminate\Support\ServiceProvider;
use faysal0x1\PaymentGateway\Models\PaymentGateway;
use faysal0x1\PaymentGateway\Models\PaymentTransaction;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/payment-gateway.php', 'payment-gateway'
        );

        $this->app->singleton('payment-gateway', function ($app) {
            return new PaymentGatewayManager($app);
        });

        $this->registerMigrations();
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishResources();
            $this->commands([
                \faysal0x1\PaymentGateway\Console\Commands\MakePaymentGatewayCrudCommand::class,
            ]);
        }
    }

    protected function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function publishResources()
    {
        $this->publishes([
            __DIR__.'/../config/payment-gateway.php' => config_path('payment-gateway.php'),
        ], 'payment-gateway-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'payment-gateway-migrations');
    }
}