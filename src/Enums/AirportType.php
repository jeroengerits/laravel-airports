<?php

declare(strict_types=1);

namespace JeroenGerits\LaravelAirports\Enums;

enum AirportType: string
{
    case Small = 'small_airport';
    case Medium = 'medium_airport';
    case Large = 'large_airport';
    case Heliport = 'heliport';
    case SeaplaneBase = 'seaplane_base';
    case Balloonport = 'balloonport';
    case Closed = 'closed';
}
