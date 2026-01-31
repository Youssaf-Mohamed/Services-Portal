<?php

namespace App\Enums\Transport;

enum Direction: string
{
    case ONE_WAY = 'one_way';
    case ROUND_TRIP = 'round_trip';
}
