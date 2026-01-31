<?php

namespace App\Enums\IdCard;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case FLAGGED = 'flagged';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Verification',
            self::VERIFIED => 'Verified',
            self::FLAGGED => 'Flagged',
        };
    }

    /**
     * Get color for UI badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::VERIFIED => 'success',
            self::FLAGGED => 'danger',
        };
    }
}
