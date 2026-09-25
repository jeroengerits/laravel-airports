<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Actions;

use JeroenGerits\LaravelAirports\Models\Airport;
use RuntimeException;

class ImportAirportsAction
{
    /**
     * @param  resource  $stream
     *
     * @throws \Throwable
     */
    public function execute($stream): int
    {
        rewind($stream);
        $header = fgetcsv($stream, escape: '');
        $fields = [
            'type', 'name', 'latitude_deg', 'longitude_deg', 'elevation_ft',
            'continent', 'iso_country', 'gps_code', 'iata_code', 'local_code',
        ];

        if ($header === false || array_diff(['id', ...$fields], $header) !== []) {
            throw new RuntimeException('The airport CSV is missing required columns.');
        }

        $header = array_map(static fn (?string $column): string => $column ?? '', $header);

        return (new Airport)->getConnection()->transaction(function () use ($stream, $header, $fields): int {
            $count = 0;
            $existingIds = Airport::query()->whereNotNull('external_id')->pluck('id', 'external_id')->all();

            while (($values = fgetcsv($stream, escape: '')) !== false) {
                if ($values === [null]) {
                    continue;
                }

                if (count($values) !== count($header)) {
                    throw new RuntimeException('The airport CSV contains a malformed row.');
                }

                $row = array_combine($header, $values);

                if ($row['id'] === null || $row['id'] === '' || $row['name'] === null || $row['name'] === '') {
                    throw new RuntimeException('Every airport must have an id and name.');
                }

                $attributes = [];

                foreach ($fields as $field) {
                    $attributes[$field] = $row[$field] === '' ? null : $row[$field];
                }

                $airport = isset($existingIds[$row['id']])
                    ? Airport::query()->whereKey($existingIds[$row['id']])->firstOrFail()
                    : new Airport;

                $airport->fill(['external_id' => $row['id'], ...$attributes])->save();
                $existingIds[$row['id']] = $airport->getKey();
                $count++;
            }

            return $count;
        });
    }
}
