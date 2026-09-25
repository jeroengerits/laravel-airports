<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Builders;

use Illuminate\Database\Eloquent\Builder;
use JeroenGerits\LaravelAirports\Enums\AirportType;
use JeroenGerits\LaravelAirports\Models\Airport;

/** @extends Builder<Airport> */
class AirportBuilder extends Builder
{
    public function inCountry(string $country): static
    {
        return $this->where($this->qualifyColumn('iso_country'), strtoupper(trim($country)));
    }

    /** @param list<string> $countries */
    public function inCountries(array $countries): static
    {
        $this->whereIn($this->qualifyColumn('iso_country'), array_map(
            static fn (string $country): string => strtoupper(trim($country)),
            $countries,
        ));

        return $this;
    }

    public function ofType(AirportType|string $type): static
    {
        return $this->where($this->qualifyColumn('type'), $type instanceof AirportType ? $type->value : $type);
    }

    public function withIataCode(string $code): static
    {
        return $this->where($this->qualifyColumn('iata_code'), strtoupper(trim($code)));
    }

    public function findByIata(string $code): ?Airport
    {
        $this->withIataCode($code)->orderBy($this->qualifyColumn('id'));

        return $this->first();
    }

    public function findByIataOrFail(string $code): Airport
    {
        $this->withIataCode($code)->orderBy($this->qualifyColumn('id'));

        return $this->firstOrFail();
    }

    public function hasIataCode(): static
    {
        $column = $this->qualifyColumn('iata_code');
        $this->whereNotNull($column)->whereRaw(new ColumnExpression("TRIM(%s) <> ''", [$column]));

        return $this;
    }

    public function withCoordinates(): static
    {
        $this->whereNotNull($this->qualifyColumn('latitude_deg'))
            ->whereNotNull($this->qualifyColumn('longitude_deg'));

        return $this;
    }

    /**
     * Search codes and names, replacing existing ordering with relevance, name, and ID.
     * Blank input returns no matches. SQL wildcard characters are treated literally.
     */
    public function search(string $term): static
    {
        $term = mb_strtolower(trim($term), 'UTF-8');

        if ($term === '') {
            $this->whereRaw('1 = 0');

            return $this;
        }

        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
        $prefix = $escaped.'%';
        $contains = '%'.$escaped.'%';
        $iata = $this->qualifyColumn('iata_code');
        $gps = $this->qualifyColumn('gps_code');
        $name = $this->qualifyColumn('name');

        $this->where(function (Builder $query) use ($iata, $gps, $name, $prefix, $contains): void {
            $query->whereRaw(new ColumnExpression("LOWER(%s) LIKE ? ESCAPE '!'", [$iata]), [$prefix])
                ->whereRaw(new ColumnExpression("LOWER(%s) LIKE ? ESCAPE '!'", [$gps]), [$prefix], 'or')
                ->whereRaw(new ColumnExpression("LOWER(%s) LIKE ? ESCAPE '!'", [$name]), [$contains], 'or');
        })->reorder()->orderBy(new ColumnExpression(
            'CASE WHEN LOWER(%s) = ? THEN 0 WHEN LOWER(%s) = ? THEN 1 '
            ."WHEN LOWER(%s) LIKE ? ESCAPE '!' OR LOWER(%s) LIKE ? ESCAPE '!' THEN 2 "
            ."WHEN LOWER(%s) LIKE ? ESCAPE '!' THEN 3 ELSE 4 END",
            [$iata, $gps, $iata, $gps, $name],
        ))->orderBy($this->qualifyColumn('name'))->orderBy($this->qualifyColumn('id'));
        $this->getQuery()->addBinding([$term, $term, $prefix, $prefix, $prefix], 'order');

        return $this;
    }
}
