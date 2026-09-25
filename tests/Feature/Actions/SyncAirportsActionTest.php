<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JeroenGerits\LaravelAirports\Actions\ImportAirportsAction;
use JeroenGerits\LaravelAirports\Actions\SyncAirportsAction;
use JeroenGerits\LaravelAirports\Models\Airport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
});

it('fails before downloading or importing when a temporary file cannot be created', function (): void {
    Http::fake();
    $importAirports = $this->mock(ImportAirportsAction::class);
    $importAirports->shouldNotReceive('execute');

    $action = new class($importAirports) extends SyncAirportsAction
    {
        protected function createTemporaryFile(): false
        {
            return false;
        }
    };

    expect(fn () => $action->execute())
        ->toThrow(RuntimeException::class, 'Unable to create a temporary CSV file.');

    Http::assertNothingSent();
});

it('downloads the CSV and delegates importing while closing the stream', function (): void {
    $url = 'https://davidmegginson.github.io/ourairports-data/airports.csv';
    $csv = airportCsv('1,EHAM,large_airport,Schiphol,52.3,4.7,-11,EU,NL,EHAM,AMS,');
    Http::fake([$url => Http::response($csv)]);
    $capturedStream = null;

    $this->mock(ImportAirportsAction::class)->shouldReceive('execute')->once()
        ->andReturnUsing(function ($stream) use (&$capturedStream, $csv): int {
            $capturedStream = $stream;
            rewind($stream);
            expect(stream_get_contents($stream))->toBe($csv);

            return 1;
        });

    expect(app(SyncAirportsAction::class)->execute())->toBe(1)
        ->and(is_resource($capturedStream))->toBeFalse();
    Http::assertSentCount(1);
});

it('propagates download failures without changing stored airports', function (): void {
    $airport = Airport::factory()->create();
    Http::fake(['*' => Http::response('Unavailable', 503)]);
    $this->mock(ImportAirportsAction::class)->shouldNotReceive('execute');

    expect(fn () => app(SyncAirportsAction::class)->execute())->toThrow(RequestException::class);
    expect(Airport::sole()->id)->toBe($airport->id);
});

it('closes the temporary stream when importing fails', function (): void {
    Http::fake(['*' => Http::response(airportCsv(''))]);
    $capturedStream = null;
    $this->mock(ImportAirportsAction::class)->shouldReceive('execute')->once()
        ->andReturnUsing(function ($stream) use (&$capturedStream): never {
            $capturedStream = $stream;

            throw new RuntimeException('Invalid CSV.');
        });

    expect(fn () => app(SyncAirportsAction::class)->execute())
        ->toThrow(RuntimeException::class, 'Invalid CSV.');
    expect(is_resource($capturedStream))->toBeFalse();
});
