<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Tests;

use JeroenGerits\LaravelAirports\LaravelAirportsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelAirportsServiceProvider::class];
    }
}
