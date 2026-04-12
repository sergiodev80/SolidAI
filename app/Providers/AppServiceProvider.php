<?php

namespace App\Providers;

use App\Models\PluginVersion;
use App\Observers\PluginVersionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        PluginVersion::observe(PluginVersionObserver::class);
    }
}
