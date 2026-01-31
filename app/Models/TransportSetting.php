<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportSetting extends Model
{
    protected $fillable = [
        'days_per_week',
        'weeks_in_month',
        'weeks_in_term',
        'registration_opens_at',
        'registration_closes_at',
        'show_capacity',
    ];

    protected $casts = [
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
        'show_capacity' => 'boolean',
    ];

    /**
     * Get the user who last updated the settings.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
