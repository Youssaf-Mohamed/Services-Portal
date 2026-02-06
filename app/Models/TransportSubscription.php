<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'request_id',
        'user_id',
        'route_id',
        'slot_id',
        'plan_id',
        'selected_days',
        'plan_type',
        'status',
        'starts_at',
        'ends_at',
        'amount_paid_expected',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'selected_days' => 'array',
        'amount_paid_expected' => 'decimal:2',
    ];

    /**
     * Get the plan for this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(TransportPlan::class, 'plan_id');
    }

    /**
     * Get the original request.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(TransportSubscriptionRequest::class, 'request_id');
    }

    /**
     * Get the user who owns this subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the route for this subscription.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(BusRoute::class, 'route_id');
    }

    /**
     * Get the schedule slot for this subscription.
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(BusScheduleSlot::class, 'slot_id');
    }

    /**
     * Get the seat reservation for this subscription.
     */
    public function reservation(): HasOne
    {
        return $this->hasOne(TransportSeatReservation::class, 'subscription_id');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get subscriptions active on a specific date.
     * If ends_at is null, considers it as indefinitely active.
     */
    public function scopeActiveOn($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where('status', 'active')
            ->where(function ($q) use ($date) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', $date);
            });
    }

    /**
     * Scope to filter by specific day of week.
     */
    public function scopeWithDay($query, string $day)
    {
        return $query->whereJsonContains('selected_days', $day);
    }

    /**
     * Check if subscription includes a specific day.
     */
    public function hasDay(string $day): bool
    {
        return in_array($day, $this->selected_days ?? []);
    }
}
