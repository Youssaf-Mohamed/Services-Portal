<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusRoute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name_ar',
        'name_en',
        'active',
        'price_one_way',
        'monthly_discount_percent',
        'term_discount_percent',
    ];

    protected $casts = [
        'active' => 'boolean',
        'price_one_way' => 'decimal:2',
        'monthly_discount_percent' => 'integer',
        'term_discount_percent' => 'integer',
    ];

    /**
     * Get all schedule slots for this route.
     */
    public function slots(): HasMany
    {
        return $this->hasMany(BusScheduleSlot::class, 'route_id');
    }

    /**
     * Get all stops for this route (ordered by sort_order).
     */
    public function stops(): BelongsToMany
    {
        return $this->belongsToMany(BusStop::class, 'bus_route_stops', 'route_id', 'stop_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('bus_route_stops.sort_order');
    }

    /**
     * Scope to get only active routes.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
