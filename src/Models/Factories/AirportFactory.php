<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Models\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeroenGerits\LaravelAirports\Models\Airport;

/**
 * @extends Factory<Airport>
 */
class AirportFactory extends Factory
{
    protected $model = Airport::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'external_id' => $this->faker->uuid(),
            'type' => $this->faker->randomElement([
                'small_airport',
                'medium_airport',
                'large_airport',
                'heliport',
                'seaplane_base',
                'balloonport',
                'closed',
            ]),
            'name' => $this->faker->city().' Airport',
            'latitude_deg' => $this->faker->latitude(),
            'longitude_deg' => $this->faker->longitude(),
            'elevation_ft' => $this->faker->numberBetween(-1500, 15000),
            'continent' => $this->faker->randomElement(['AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA']),
            'iso_country' => $this->faker->countryCode(),
            'gps_code' => strtoupper($this->faker->lexify('????')),
            'iata_code' => strtoupper($this->faker->lexify('???')),
            'local_code' => strtoupper($this->faker->bothify('??##')),
        ];
    }
}
