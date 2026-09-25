<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use JeroenGerits\LaravelAirports\Builders\AirportBuilder;
use JeroenGerits\LaravelAirports\Models\Factories\AirportFactory;

/**
 * @property string $id
 * @property string|null $external_id
 * @property string|null $type
 * @property string $name
 * @property float|null $latitude_deg
 * @property float|null $longitude_deg
 * @property int|null $elevation_ft
 * @property string|null $continent
 * @property string|null $iso_country
 * @property string|null $gps_code
 * @property string|null $iata_code
 * @property string|null $local_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'latitude_deg' => 'float',
            'longitude_deg' => 'float',
            'elevation_ft' => 'integer',
        ];
    }

    public function newEloquentBuilder($query): AirportBuilder
    {
        return new AirportBuilder($query);
    }

    public static function query(): AirportBuilder
    {
        $query = parent::query();

        if (! $query instanceof AirportBuilder) {
            throw new \LogicException('Airport queries must use AirportBuilder.');
        }

        return $query;
    }
}
