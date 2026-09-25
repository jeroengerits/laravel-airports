<?php

declare(strict_types=1);

use JeroenGerits\LaravelAirports\LaravelAirportsServiceProvider;

it('registers the package service provider', function (): void {
    expect(
        $this->app->providerIsLoaded(LaravelAirportsServiceProvider::class)
    )->toBeTrue();
});
