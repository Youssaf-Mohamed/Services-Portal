<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportSeatReservation extends Model
{
    protected $fillable = [
        'slot_id',
        'subscription_id',
        'reserved_at',
        'released_at',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    /**
     * Get the slot this reservation is for.
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(BusScheduleSlot::class, 'slot_id');
    }

    /**
     * Get the subscription this reservation belongs to.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TransportSubscription::class, 'subscription_id');
    }

    /**
     * Scope to get only active (unreleased) reservations.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('released_at');
    }
}
