<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class WrapPhpServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            'wrapphp',
            'App\Services\WrapPhp\WrapPhpService'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
