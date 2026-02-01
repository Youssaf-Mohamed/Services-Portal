<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusScheduleSlot extends Model
{
    protected $fillable = [
        'route_id',
        'day_of_week',
        'direction',
        'time',
        'capacity',
        'active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'capacity' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * Get the route this slot belongs to.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(BusRoute::class, 'route_id');
    }

    /**
     * Get all seat reservations for this slot.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(TransportSeatReservation::class, 'slot_id');
    }

    /**
     * Get only active (unreleased) seat reservations for this slot.
     */
    public function activeReservations(): HasMany
    {
        return $this->hasMany(TransportSeatReservation::class, 'slot_id')
            ->whereNull('released_at');
    }

    /**
     * Scope to get only active slots.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
