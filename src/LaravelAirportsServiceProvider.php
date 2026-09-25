<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports;

use Illuminate\Support\ServiceProvider;
use JeroenGerits\LaravelAirports\Console\Commands\SyncAirportsCommand;

final class LaravelAirportsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncAirportsCommand::class,
            ]);
        }
    }
}
