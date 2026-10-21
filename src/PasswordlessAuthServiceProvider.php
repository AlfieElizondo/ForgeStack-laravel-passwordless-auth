<?php

declare(strict_types=1);

namespace ForgeStack\PasswordlessAuth;

use Illuminate\Support\ServiceProvider;

final class PasswordlessAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/passwordless-auth.php', 'passwordless-auth');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/passwordless-auth.php' => config_path('passwordless-auth.php'),
            ], 'passwordless-auth-config');
        }
    }
}
