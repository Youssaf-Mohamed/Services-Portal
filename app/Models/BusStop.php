<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BusStop extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'lat',
        'lng',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    /**
     * Get all routes that include this stop.
     */
    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(BusRoute::class, 'bus_route_stops', 'stop_id', 'route_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('bus_route_stops.sort_order');
    }
}
