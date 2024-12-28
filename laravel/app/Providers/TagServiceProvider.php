<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TagServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            'tag',
            'App\Services\HtmlTag\TagService'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        
    }
}
