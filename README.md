# Laravel Airports

Laravel package with data of all airports in the world

## Requirements

- PHP 8.5 or newer
- Laravel 13.0 or newer

## Installation

```sh
composer require jeroengerits/laravel-airports
```

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
