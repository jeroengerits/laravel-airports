<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use JeroenGerits\LaravelAirports\Actions\SyncAirportsAction;

uses(RefreshDatabase::class);

it('imports airports through the command and reports success', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://davidmegginson.github.io/ourairports-data/airports.csv' => Http::response(
            airportCsv('1,EHAM,large_airport,Schiphol,52.3,4.7,-11,EU,NL,EHAM,AMS,')
        ),
    ]);

    $this->artisan('airport:sync')->expectsOutput('Synced 1 airports.')->assertSuccessful();

    $this->assertDatabaseHas('airports', ['external_id' => '1', 'name' => 'Schiphol']);
});

it('reports action failures and exits unsuccessfully', function (): void {
    $this->mock(SyncAirportsAction::class)
        ->shouldReceive('execute')->once()->andThrow(new RuntimeException('Download failed.'));

    $this->artisan('airport:sync')
        ->expectsOutput('Airport sync failed: Download failed.')
        ->assertFailed();
});
