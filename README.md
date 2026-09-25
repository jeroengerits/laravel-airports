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

`Airport::query()` returns a typed `AirportBuilder` for IDE completion. Filters
remain chainable until you call an Eloquent terminal method such as `get()`,
`first()`, `sole()`, or `paginate()`.

### Find one airport

```php
use JeroenGerits\LaravelAirports\Models\Airport;

$airport = Airport::query()->findByIata(' ams '); // Airport|null
$airport = Airport::query()->findByIataOrFail('AMS'); // Airport or ModelNotFoundException

// The original composable filter is also available.
$airport = Airport::query()->inCountry('NL')->withIataCode('AMS')->first();
```

Country and IATA filters uppercase codes and trim surrounding whitespace. Lookup
helpers respect preceding filters. Codes are not unique in the schema; lookups
append ID ordering to select a stable first match. Use `withIataCode()->sole()`
when you want to reject duplicate results. Unknown codes return `null` from
`findByIata()`; `findByIataOrFail()` integrates with Laravel's model-not-found handling.

### Filter and paginate

```php
use JeroenGerits\LaravelAirports\Enums\AirportType;
use JeroenGerits\LaravelAirports\Models\Airport;

$airports = Airport::query()
    ->inCountries(['nl', 'BE'])
    ->ofType(AirportType::Large)
    ->hasIataCode()
    ->orderBy('name')
    ->orderBy('id')
    ->paginate(25);
```

`inCountry('NL')` filters one country; `inCountries([])` returns no matches.
`hasIataCode()` excludes null, empty, and space-only codes. An IATA code does not
guarantee scheduled passenger service.

`ofType()` accepts an `AirportType` or an existing string such as `'large_airport'`.
Enum cases are `Small`, `Medium`, `Large`, `Heliport`, `SeaplaneBase`, `Balloonport`,
and `Closed`. The model's `type` attribute remains a nullable string.

### Search by code or name

```php
$suggestions = Airport::query()
    ->inCountry('NL')
    ->search('schiphol')
    ->limit(10)
    ->get();
```

`search()` returns the builder and ranks results in this order:

1. Exact IATA-code match.
2. Exact GPS-code match.
3. IATA- or GPS-code prefix match.
4. Airport-name prefix match.
5. Airport-name substring match.

Name and ID break ties. Search replaces prior ordering with relevance ordering;
filters before and after it still apply. Whitespace-only and empty input return
no matches. `%`, `_`, and `!` are literal characters, not search wildcards.
Input is bound as query parameters.

Search uses SQL `LOWER()` for case-insensitive matching. Non-ASCII case folding
and accent matching depend on your database/collation (SQLite's built-in
`LOWER()` only folds ASCII). Search is not fuzzy and searches existing codes and
airport names, not a separate city dataset.

### Airports for a map

```php
$airports = Airport::query()
    ->inCountry('NL')
    ->withCoordinates()
    ->get(['id', 'name', 'latitude_deg', 'longitude_deg']);
```

`withCoordinates()` requires both coordinates; zero is a valid coordinate.
Coordinates are cast to floats and elevation to an integer; missing values remain
`null`.

### Autocomplete endpoint recipe

Add an endpoint in your application's routes, for example:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use JeroenGerits\LaravelAirports\Models\Airport;

Route::get('/airports/search', function (Request $request) {
    $input = $request->validate(['q' => ['nullable', 'string', 'max:100']]);

    return Airport::query()
        ->hasIataCode()
        ->search($input['q'] ?? '')
        ->limit(10)
        ->get(['id', 'iata_code', 'name', 'iso_country'])
        ->map(fn (Airport $airport) => [
            'value' => $airport->id,
            'label' => "{$airport->iata_code} — {$airport->name}",
            'country' => $airport->iso_country,
        ]);
});
```

For a useful picker in your application:

- Display the code and airport name, with the country as secondary context.
  Translate ISO country codes to localized names in your UI if desired.
- Debounce input by 200–300 ms and cancel or ignore stale requests.
- Show loading, no-results, and retry states; clear suggestions for blank input.
- Support keyboard navigation and accessible combobox semantics.
- Store the UUID from `value` as the selected airport identifier.
- Add country/type restrictions explicitly for your use case.

### Query indexes and upgrades

Run `php artisan migrate` after upgrading to add nonunique indexes on `iata_code`,
`gps_code`, and `(iso_country, type)` to existing installations. The original
airport table and records are preserved.

These indexes support exact lookups and country/type filters. They do not promise
to accelerate `LOWER()` expressions or substring search. Measure real query plans
before adding database-specific full-text/trigram search or a search service.

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
