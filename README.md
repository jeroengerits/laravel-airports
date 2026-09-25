# Laravel Airports

Laravel package with data of all airports in the world

## Requirements

- PHP 8.5 or newer
- Laravel 13.0 or newer

## Installation

```sh
composer require jeroengerits/laravel-airports
```

## Sync airports

Run migrations, then download and import the OurAirports CSV:

```sh
php artisan migrate
php artisan airport:sync
```

During package development, first create Testbench's persistent SQLite database
and run migrations:

```sh
vendor/bin/testbench package:create-sqlite-db
vendor/bin/testbench migrate
vendor/bin/testbench airport:sync
```

The database setup is only needed once per Testbench installation. Without the
SQLite file, Testbench falls back to an in-memory database that is discarded after
each command, so running migrations separately will not prepare it for syncing.
Subsequent syncs only require `vendor/bin/testbench airport:sync`.

## Querying airports

```php
use JeroenGerits\LaravelAirports\Models\Airport;

$airport = Airport::query()->withIataCode('AMS')->first();
$airports = Airport::query()->inCountry('NL')->ofType('large_airport')->get();
```

Country and IATA filters accept lowercase codes and trim surrounding whitespace.
Coordinates are cast to floats and elevation to an integer; missing values remain
`null`.

## Development

```sh
composer install
composer test
composer test-coverage
composer lint
composer analyse
```

## CI

The GitHub Actions workflow runs Composer validation, dependency installation, Pint, PHPStan, and Pest on PHP 8.5.

## Release process

1. Confirm the supported PHP and Laravel versions.
2. Add and test the intended airport data and API in a versioned change.
3. Run `composer validate --strict --check-lock`, `composer lint`, `composer analyse`, and `composer test`.
4. Review the changelog and dataset licensing before tagging a release.
5. Tag the release and publish it through the configured Composer repository.

## License

This package is released under the MIT License.
