<?php

declare(strict_types=1);

use JeroenGerits\LaravelAirports\Tests\TestCase;

uses(TestCase::class)->in('Feature');

function airportCsv(string $rows): string
{
    return "id,ident,type,name,latitude_deg,longitude_deg,elevation_ft,continent,iso_country,gps_code,iata_code,local_code\n".$rows;
}
