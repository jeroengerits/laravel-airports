<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SyncAirportsAction
{
    public function __construct(private ImportAirportsAction $importAirports) {}

    /**
     * @throws RequestException
     * @throws \Throwable
     * @throws ConnectionException
     */
    public function execute(): int
    {
        $stream = $this->createTemporaryFile();

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

    /** @return resource|false */
    protected function createTemporaryFile()
    {
        return tmpfile();
    }
}
