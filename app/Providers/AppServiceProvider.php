<?php

namespace App\Providers;

use App\Contracts\PembayaranGateway;
use App\Gateways\GatewaySimulasi;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PembayaranGateway::class, GatewaySimulasi::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
