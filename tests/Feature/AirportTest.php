<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use JeroenGerits\LaravelAirports\Models\Airport;

uses(RefreshDatabase::class);

it('filters airports by country and type using chainable scopes', function (): void {
    $matching = Airport::factory()->create(['iso_country' => 'NL', 'type' => 'large_airport']);
    Airport::factory()->create(['iso_country' => 'NL', 'type' => 'small_airport']);
    Airport::factory()->create(['iso_country' => 'BE', 'type' => 'large_airport']);

    expect(Airport::query()->inCountry(' nl ')->ofType('large_airport')->sole()->id)
        ->toBe($matching->id);
});

it('finds airports by a normalized IATA code', function (): void {
    $matching = Airport::factory()->create(['iata_code' => 'AMS']);
    Airport::factory()->create(['iata_code' => 'BRU']);
    Airport::factory()->create(['iata_code' => null]);

    expect(Airport::query()->withIataCode(' ams ')->sole()->id)->toBe($matching->id)
        ->and(Airport::query()->withIataCode('XXX')->exists())->toBeFalse();
});

it('preserves missing coordinates and elevation as null', function (): void {
    $airport = Airport::create(['name' => 'Unknown Airport'])->refresh();

    expect($airport->latitude_deg)->toBeNull()
        ->and($airport->longitude_deg)->toBeNull()
        ->and($airport->elevation_ft)->toBeNull();
});

it('creates and retrieves an airport with a UUID and numeric casts', function (): void {
    $airport = Airport::create([
        'external_id' => 'EHAM',
        'type' => 'large_airport',
        'name' => 'Amsterdam Airport Schiphol',
        'latitude_deg' => '52.3086010',
        'longitude_deg' => '4.7638900',
        'elevation_ft' => '-11',
        'continent' => 'EU',
        'iso_country' => 'NL',
        'gps_code' => 'EHAM',
        'iata_code' => 'AMS',
        'local_code' => 'EHAM',
    ]);

    $stored = Airport::findOrFail($airport->id);

    expect(Str::isUuid($stored->id))->toBeTrue()
        ->and($stored->external_id)->toBe('EHAM')
        ->and($stored->type)->toBe('large_airport')
        ->and($stored->name)->toBe('Amsterdam Airport Schiphol')
        ->and($stored->latitude_deg)->toBe(52.308601)
        ->and($stored->longitude_deg)->toBe(4.76389)
        ->and($stored->elevation_ft)->toBe(-11)
        ->and($stored->continent)->toBe('EU')
        ->and($stored->iso_country)->toBe('NL')
        ->and($stored->gps_code)->toBe('EHAM')
        ->and($stored->iata_code)->toBe('AMS')
        ->and($stored->local_code)->toBe('EHAM');
});

it('generates valid airport data that can be persisted', function (): void {
    $airport = Airport::factory()->create()->refresh();

    expect(Str::isUuid($airport->id))->toBeTrue()
        ->and(Str::isUuid($airport->external_id))->toBeTrue()
        ->and($airport->name)->toEndWith(' Airport')
        ->and($airport->type)->toBeIn([
            'small_airport', 'medium_airport', 'large_airport',
            'heliport', 'seaplane_base', 'balloonport', 'closed',
        ])
        ->and($airport->latitude_deg)->toBeFloat()->toBeBetween(-90, 90)
        ->and($airport->longitude_deg)->toBeFloat()->toBeBetween(-180, 180)
        ->and($airport->elevation_ft)->toBeInt()->toBeBetween(-1500, 15000)
        ->and($airport->continent)->toBeIn(['AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA'])
        ->and($airport->iso_country)->toMatch('/^[A-Z]{2}$/')
        ->and($airport->gps_code)->toMatch('/^[A-Z]{4}$/')
        ->and($airport->iata_code)->toMatch('/^[A-Z]{3}$/')
        ->and($airport->local_code)->toMatch('/^[A-Z]{2}[0-9]{2}$/');

    $this->assertDatabaseHas('airports', ['id' => $airport->id]);
});
