<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportPlan extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'plan_type',
        'allowed_days_per_week',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'allowed_days_per_week' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get all subscription requests using this plan.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(TransportSubscriptionRequest::class, 'plan_id');
    }

    /**
     * Get all active subscriptions using this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(TransportSubscription::class, 'plan_id');
    }

    /**
     * Scope to get only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to filter by plan type.
     */
    public function scopePlanType($query, string $type)
    {
        return $query->where('plan_type', $type);
    }

    /**
     * Get localized name based on app locale.
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }
}
