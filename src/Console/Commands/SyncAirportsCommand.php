<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Console\Commands;

use Illuminate\Console\Command;
use JeroenGerits\LaravelAirports\Actions\SyncAirportsAction;
use Throwable;

class SyncAirportsCommand extends Command
{
    protected $signature = 'airport:sync';

    protected $description = 'Sync airports from OurAirports';

    public function handle(SyncAirportsAction $syncAirports): int
    {
        try {
            $count = $syncAirports->execute();

            $this->info("Synced {$count} airports.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Airport sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
