<?php

namespace App\Providers;

use App\Database\Connectors\PostgresConnector;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Neon TLS: forward channel_binding (+ ssl*) on the pgsql PDO DSN.
        $this->app->bind('db.connector.pgsql', PostgresConnector::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
