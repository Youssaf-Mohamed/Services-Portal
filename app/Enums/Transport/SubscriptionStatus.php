<?php

namespace App\Enums\Transport;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case WAITLISTED = 'waitlisted';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
}
