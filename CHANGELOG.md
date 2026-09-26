# Changelog

All notable changes to this package are documented in this file.

## Unreleased

## v0.1.0

- Added a typed airport query builder, AirportType enum, normalized IATA lookup helpers, and country/code/coordinate filters.
- Added ranked code/name search with literal wildcard handling and stable result ordering.
- Added an upgrade-safe migration for nonunique airport lookup indexes.
- Documented lookup, pagination, map queries, and autocomplete integration.
- Prepared the repository as a Laravel 13 package.
- Updated the development toolchain to Testbench 11, Pest 5, and PHPUnit 13.
- Added the auto-discovered service provider and Testbench harness.
- Added Pest, Laravel Pint, PHPStan, and GitHub Actions checks.
- Documented the source-neutral airport data and API roadmap.
