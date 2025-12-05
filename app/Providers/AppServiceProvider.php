<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ⭐ FUERZA HTTPS EN PRODUCCIÓN
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}