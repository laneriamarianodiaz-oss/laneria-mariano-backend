<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Mail\BrevoTransport;

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

        // ⭐ REGISTRA BREVO TRANSPORT
        Mail::extend('brevo', function () {
            return new BrevoTransport();
        });
    }
}