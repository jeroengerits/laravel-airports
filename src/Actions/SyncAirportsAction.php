<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Actions;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SyncAirportsAction
{
    public function __construct(private ImportAirportsAction $importAirports) {}

    public function execute(): int
    {
        $stream = tmpfile();

        if ($stream === false) {
            throw new RuntimeException('Unable to create a temporary CSV file.');
        }

        try {
            Http::connectTimeout(15)
                ->timeout(180)
                ->withOptions(['sink' => $stream])
                ->get('https://davidmegginson.github.io/ourairports-data/airports.csv')
                ->throw();

            return $this->importAirports->execute($stream);
        } finally {
            fclose($stream);
        }
    }
}
