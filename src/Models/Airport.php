<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use JeroenGerits\LaravelAirports\Models\Factories\AirportFactory;

class Airport extends Model
{
    /** @use HasFactory<AirportFactory> */
    use HasFactory, HasUuids;

    protected static string $factory = AirportFactory::class;

    /** @var list<string> */
    protected $fillable = [
        'external_id',
        'type',
        'name',
        'latitude_deg',
        'longitude_deg',
        'elevation_ft',
        'continent',
        'iso_country',
        'gps_code',
        'iata_code',
        'local_code',
    ];

    protected function casts(): array
    {
        return [
            'latitude_deg' => 'float',
            'longitude_deg' => 'float',
            'elevation_ft' => 'integer',
        ];
    }
}
