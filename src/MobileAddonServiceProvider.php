<?php

namespace App\MobileAddon;

use Illuminate\Support\ServiceProvider;

class MobileAddonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->mergeConfigFrom(
            __DIR__.'/../config/mobileaddon.php', 'mobileaddon'
        );
        config(['cors.exposed_headers' => ['X-Module-Version', 'X-CurseHosting-Version', 'X-Root-Admin']]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        //
    }
}
