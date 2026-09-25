<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Tests;

use JeroenGerits\LaravelAirports\LaravelAirportsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [LaravelAirportsServiceProvider::class];
    }
}
