<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransportSubscriptionRequest extends Model
{
    protected $fillable = [
        'user_id',
        'route_id',
        'slot_id',
        'plan_id',
        'selected_days',
        'plan_type',
        'direction',
        'status',
        'payment_status',
        'payment_flag_reason',
        'payment_verified_at',
        'payment_verified_by',
        'payment_method_id',
        'paid_from_number',
        'paid_at',
        'proof_path',
        'pricing_snapshot',
        'amount_expected',
        'rejection_reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'payment_verified_at' => 'datetime',
        'selected_days' => 'array',
        'pricing_snapshot' => 'array',
        'amount_expected' => 'decimal:2',
    ];

    /**
     * Get the plan for this request.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(TransportPlan::class, 'plan_id');
    }

    /**
     * Get the user who made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the route for this request.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(BusRoute::class, 'route_id');
    }

    /**
     * Get the schedule slot for this request.
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(BusScheduleSlot::class, 'slot_id');
    }

    /**
     * Get the payment method used.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * Get the admin who approved this request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the admin who verified the payment.
     */
    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    /**
     * Get the subscription created from this request.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(TransportSubscription::class, 'request_id');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by payment status.
     */
    public function scopePaymentStatus($query, string $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Scope to get requests with verified payment.
     */
    public function scopePaymentVerified($query)
    {
        return $query->where('payment_status', 'verified');
    }

    /**
     * Check if payment is verified.
     */
    public function isPaymentVerified(): bool
    {
        return $this->payment_status === 'verified';
    }
}

