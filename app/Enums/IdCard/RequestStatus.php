<?php

namespace App\Enums\IdCard;

enum RequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case DELIVERED = 'delivered';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::READY_FOR_PICKUP => 'Ready for Pickup',
            self::DELIVERED => 'Delivered',
        };
    }

    /**
     * Get color for UI badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'info',
            self::REJECTED => 'danger',
            self::READY_FOR_PICKUP => 'success',
            self::DELIVERED => 'secondary',
        };
    }

    /**
     * Check if this is an "open" status (blocks new requests of same type).
     */
    public function isOpen(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::APPROVED,
            self::READY_FOR_PICKUP,
        ]);
    }

    /**
     * Check if this status allows payment verification.
     */
    public function allowsPaymentVerification(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if this status allows approval.
     */
    public function allowsApproval(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if this status allows rejection.
     */
    public function allowsRejection(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if this status allows marking as ready.
     */
    public function allowsReady(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Check if this status allows marking as delivered.
     */
    public function allowsDelivery(): bool
    {
        return $this === self::READY_FOR_PICKUP;
    }
}
