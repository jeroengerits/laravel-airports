<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use JeroenGerits\LaravelAirports\Actions\ImportAirportsAction;
use JeroenGerits\LaravelAirports\Models\Airport;

uses(RefreshDatabase::class);

function importAirportCsv(string $csv): int
{
    $stream = tmpfile();

    try {
        fwrite($stream, $csv);

        return app(ImportAirportsAction::class)->execute($stream);
    } finally {
        fclose($stream);
    }
}

it('imports airports and updates them without changing their UUIDs', function (): void {
    expect(importAirportCsv(airportCsv('1,EHAM,large_airport,"Schiphol, Amsterdam",52.308601,4.76389,-11,EU,NL,EHAM,AMS,')))->toBe(1);

    $airport = Airport::sole();
    $id = $airport->id;

    expect(Str::isUuid($id))->toBeTrue()
        ->and($airport->external_id)->toBe('1')
        ->and($airport->name)->toBe('Schiphol, Amsterdam')
        ->and($airport->latitude_deg)->toBe(52.308601)
        ->and($airport->longitude_deg)->toBe(4.76389)
        ->and($airport->elevation_ft)->toBe(-11)
        ->and($airport->local_code)->toBeNull();

    expect(importAirportCsv(airportCsv("1,EHAM,large_airport,Updated Schiphol,52.3,4.7,,EU,NL,EHAM,AMS,\n\n2,TEST,small_airport,Test Airport,0,0,,,,,,")))->toBe(2);

    expect(Airport::count())->toBe(2)
        ->and($airport->refresh()->id)->toBe($id)
        ->and($airport->name)->toBe('Updated Schiphol')
        ->and($airport->elevation_ft)->toBeNull()
        ->and(Airport::where('external_id', '2')->firstOrFail()->latitude_deg)->toBe(0.0);

});

it('rejects a CSV without the required columns', function (): void {
    expect(fn () => importAirportCsv("id,name\n1,Test"))
        ->toThrow(RuntimeException::class, 'The airport CSV is missing required columns.');

    $this->assertDatabaseCount('airports', 0);
});

it('rolls back the import when a later row is invalid', function (string $row, string $message): void {
    expect(fn () => importAirportCsv(airportCsv("1,EHAM,large_airport,Schiphol,52.3,4.7,-11,EU,NL,EHAM,AMS,\n".$row)))
        ->toThrow(RuntimeException::class, $message);

    $this->assertDatabaseCount('airports', 0);
})->with([
    'malformed row' => ['2,Invalid', 'The airport CSV contains a malformed row.'],
    'missing name' => ['2,TEST,small_airport,,0,0,,,,,,', 'Every airport must have an id and name.'],
    'missing id' => [',TEST,small_airport,Test,0,0,,,,,,', 'Every airport must have an id and name.'],
]);

it('retains airports absent from the downloaded feed', function (): void {
    $airport = Airport::factory()->create();
    expect(importAirportCsv(airportCsv('')))->toBe(0);

    expect(Airport::sole()->id)->toBe($airport->id);
});

it('restores existing airport values when a later row is invalid', function (): void {
    $airport = Airport::factory()->create(['external_id' => '1', 'name' => 'Original Airport']);
    $original = $airport->refresh()->getAttributes();

    expect(fn () => importAirportCsv(airportCsv("1,EHAM,large_airport,Updated Airport,52.3,4.7,-11,EU,NL,EHAM,AMS,\n2,Invalid")))
        ->toThrow(RuntimeException::class, 'The airport CSV contains a malformed row.');

    expect($airport->refresh()->getAttributes())->toBe($original);
    $this->assertDatabaseCount('airports', 1);
});
