<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $base_url = env('APP_URL');
        \URL::forceRootUrl($base_url);
        if (strpos($base_url, 'https://') === 0) {
            \URL::forceScheme('https');
        }
    }
}
