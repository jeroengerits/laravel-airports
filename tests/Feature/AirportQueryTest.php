<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use JeroenGerits\LaravelAirports\Builders\AirportBuilder;
use JeroenGerits\LaravelAirports\Enums\AirportType;
use JeroenGerits\LaravelAirports\Models\Airport;

uses(RefreshDatabase::class);

it('exposes a typed builder and accepts enums without changing stored type values', function (): void {
    $airport = Airport::factory()->create(['type' => 'large_airport', 'iso_country' => 'NL']);
    Airport::factory()->create(['type' => 'small_airport', 'iso_country' => 'BE']);

    expect(Airport::query())->toBeInstanceOf(AirportBuilder::class)
        ->and(Airport::query()->inCountries([' nl ', 'be'])->ofType(AirportType::Large)->sole()->id)->toBe($airport->id)
        ->and($airport->refresh()->type)->toBe('large_airport')
        ->and(Airport::query()->inCountries([])->count())->toBe(0);
});

it('provides nullable and throwing IATA lookups that respect filters', function (): void {
    $airport = Airport::factory()->create(['iata_code' => 'AMS', 'iso_country' => 'NL']);

    expect(Airport::query()->findByIata(' ams ')?->id)->toBe($airport->id)
        ->and(Airport::query()->findByIataOrFail('ams')->id)->toBe($airport->id)
        ->and(Airport::query()->findByIata('XXX'))->toBeNull()
        ->and(Airport::query()->inCountry('BE')->findByIata('AMS'))->toBeNull();

    expect(fn () => Airport::query()->findByIataOrFail('XXX'))->toThrow(ModelNotFoundException::class);
});

it('selects usable IATA codes and complete coordinates including zero', function (): void {
    $airport = Airport::factory()->create(['iata_code' => 'AMS', 'latitude_deg' => 0, 'longitude_deg' => 0]);
    foreach ([null, '', '   '] as $code) {
        Airport::factory()->create(['iata_code' => $code]);
    }
    Airport::factory()->create(['latitude_deg' => null]);
    Airport::factory()->create(['longitude_deg' => null]);

    expect(Airport::query()->hasIataCode()->withCoordinates()->sole()->id)->toBe($airport->id);
});

it('ranks exact codes before prefixes and name matches with stable ties', function (): void {
    $substring = Airport::factory()->create(['name' => 'Airport ams', 'iata_code' => null, 'gps_code' => null]);
    $name = Airport::factory()->create(['name' => 'Ams Airport', 'iata_code' => null, 'gps_code' => null]);
    $prefix = Airport::factory()->create(['name' => 'Z Prefix', 'iata_code' => null, 'gps_code' => 'AMST']);
    $gps = Airport::factory()->create(['name' => 'Z GPS', 'iata_code' => null, 'gps_code' => 'AMS']);
    $iata = Airport::factory()->create(['name' => 'Z IATA', 'iata_code' => 'AMS', 'gps_code' => null]);
    Airport::factory()->create(['name' => 'Unrelated', 'iata_code' => 'XYZ', 'gps_code' => null]);

    expect(Airport::query()->orderByDesc('name')->search(' aMs ')->pluck('id')->all())
        ->toBe([$iata->id, $gps->id, $prefix->id, $name->id, $substring->id]);

    $tie = Airport::factory()->create(['name' => 'Airport ams', 'iata_code' => null, 'gps_code' => null]);
    $ids = [$substring->id, $tie->id];
    sort($ids);

    expect(Airport::query()->search('ams')->offset(4)->limit(2)->pluck('id')->all())->toBe($ids);
});

it('groups search conditions within country and type filters', function (): void {
    $match = Airport::factory()->create(['name' => 'Schiphol', 'iso_country' => 'NL', 'type' => 'large_airport']);
    Airport::factory()->create(['name' => 'Schiphol', 'iso_country' => 'BE', 'type' => 'large_airport']);
    Airport::factory()->create(['name' => 'Schiphol', 'iso_country' => 'NL', 'type' => 'small_airport']);

    expect(Airport::query()->inCountry('NL')->search('SCHIP')->ofType(AirportType::Large)->sole()->id)->toBe($match->id)
        ->and(Airport::query()->search('SCHIP')->inCountry('NL')->ofType(AirportType::Large)->sole()->id)->toBe($match->id);
});

it('treats wildcard and quote characters as literal search input', function (string $term): void {
    $airport = Airport::factory()->create(['name' => 'Airport '.$term.' Terminal', 'iata_code' => null, 'gps_code' => null]);
    Airport::factory()->create(['name' => 'Airport Ordinary Terminal', 'iata_code' => null, 'gps_code' => null]);

    expect(Airport::query()->search($term)->sole()->id)->toBe($airport->id);
})->with(['%', '_', '!', '50!%_', "O'Hare", "' OR 1=1 --"]);

it('returns no matches for blank or unknown terms and supports pagination', function (): void {
    Airport::factory()->count(3)->create(['name' => 'Schiphol', 'iata_code' => 'AMS']);

    expect(Airport::query()->search('   ')->count())->toBe(0)
        ->and(Airport::query()->search('')->count())->toBe(0)
        ->and(Airport::query()->search('nonexistent-airport')->count())->toBe(0);

    $page = Airport::query()->search('schip')->paginate(2);
    expect($page->total())->toBe(3)->and($page->items())->toHaveCount(2);
});

it('adds nonunique lookup indexes and can roll them back without losing airports', function (): void {
    $airport = Airport::factory()->create();
    $migration = require __DIR__.'/../../database/migrations/2026_09_25_000001_add_airport_query_indexes.php';

    expect(Schema::hasIndex('airports', ['iata_code']))->toBeTrue()
        ->and(Schema::hasIndex('airports', ['gps_code']))->toBeTrue()
        ->and(Schema::hasIndex('airports', ['iso_country', 'type']))->toBeTrue();

    $migration->down();
    expect(Schema::hasIndex('airports', ['iata_code']))->toBeFalse();
    $migration->up();

    Airport::factory()->create(['iata_code' => $airport->iata_code]);
    expect(Airport::query()->find($airport->id)?->id)->toBe($airport->id)
        ->and(Airport::query()->withIataCode($airport->iata_code)->count())->toBe(2);
});
