<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusRouteStop extends Model
{
    protected $fillable = [
        'route_id',
        'stop_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the route this belongs to.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(BusRoute::class, 'route_id');
    }

    /**
     * Get the stop.
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(BusStop::class, 'stop_id');
    }
}
