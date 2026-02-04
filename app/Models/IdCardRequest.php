<?php

namespace App\Models;

use App\Enums\IdCard\PaymentStatus;
use App\Enums\IdCard\RequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdCardRequest extends Model
{
    protected $fillable = [
        'user_id',
        'type_id',
        'status',
        'amount_snapshot',
        'transaction_number',
        'paid_from_number',
        'transfer_time',
        'transfer_screenshot_path',
        'new_photo_path',
        'issue_description',
        'payment_status',
        'payment_flag_reason',
        'payment_verified_by',
        'payment_verified_at',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'ready_at',
        'delivered_at',
        'delivered_by',
    ];

    protected $casts = [
        'amount_snapshot' => 'decimal:2',
        'transfer_time' => 'datetime',
        'payment_verified_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
        'status' => RequestStatus::class,
        'payment_status' => PaymentStatus::class,
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the user who made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ID card type for this request.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(IdCardType::class, 'type_id');
    }

    /**
     * Get the admin who reviewed (approved/rejected) this request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the admin who verified the payment.
     */
    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    /**
     * Get the admin who marked as delivered.
     */
    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string|RequestStatus $status)
    {
        $value = $status instanceof RequestStatus ? $status->value : $status;
        return $query->where('status', $value);
    }

    /**
     * Scope to filter by payment status.
     */
    public function scopePaymentStatus($query, string|PaymentStatus $status)
    {
        $value = $status instanceof PaymentStatus ? $status->value : $status;
        return $query->where('payment_status', $value);
    }

    /**
     * Scope to get "open" requests (pending, approved, ready_for_pickup).
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            RequestStatus::PENDING->value,
            RequestStatus::APPROVED->value,
            RequestStatus::READY_FOR_PICKUP->value,
        ]);
    }

    /**
     * Scope to filter by type code.
     */
    public function scopeOfTypeCode($query, string $typeCode)
    {
        return $query->whereHas('type', fn($q) => $q->where('code', $typeCode));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // State Machine Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if payment is verified.
     */
    public function isPaymentVerified(): bool
    {
        return $this->payment_status === PaymentStatus::VERIFIED;
    }

    /**
     * Check if payment verification is allowed.
     */
    public function canVerifyPayment(): bool
    {
        return $this->status->allowsPaymentVerification();
    }

    /**
     * Check if flagging payment is allowed.
     */
    public function canFlagPayment(): bool
    {
        return $this->status->allowsPaymentVerification();
    }

    /**
     * Check if approval is allowed.
     */
    public function canBeApproved(): bool
    {
        return $this->status->allowsApproval() && $this->isPaymentVerified();
    }

    /**
     * Check if rejection is allowed.
     */
    public function canBeRejected(): bool
    {
        return $this->status->allowsRejection();
    }

    /**
     * Check if marking as ready is allowed.
     */
    public function canBeReadied(): bool
    {
        return $this->status->allowsReady();
    }

    /**
     * Check if marking as delivered is allowed.
     */
    public function canBeDelivered(): bool
    {
        return $this->status->allowsDelivery();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // File Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if this request has a new photo attachment.
     */
    public function hasNewPhoto(): bool
    {
        return !empty($this->new_photo_path);
    }
}
