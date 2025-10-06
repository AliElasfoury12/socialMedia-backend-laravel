<?php

namespace App\Providers;

use App\Auth\Guards\JWT_Guard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Auth::extend('jwt_guard', function ($app, $name, array $config)
        {
            return new JWT_Guard($app['request']);
        });
    }
}
