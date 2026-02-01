<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdCardSetting extends Model
{
    protected $fillable = [
        'payment_account_number',
        'payment_account_name',
        'payment_instructions',
        'service_enabled',
        'updated_by',
    ];

    protected $casts = [
        'service_enabled' => 'boolean',
    ];

    /**
     * Get the user who last updated the settings.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the singleton settings instance.
     */
    public static function instance(): ?self
    {
        return static::first();
    }

    /**
     * Check if the service is currently enabled.
     */
    public static function isServiceEnabled(): bool
    {
        $settings = static::instance();
        return $settings ? $settings->service_enabled : false;
    }
}
