<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Send a notification to a user.
     */
    public function notify(User $user, string $type, string $title, string $message, ?string $link = null): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::forUser($user->id)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::forUser($user->id)->unread()->count();
    }

    /**
     * Sends a notification to the student confirming their request submission.
     */
    public function notifyRequestSubmitted(User $student, int $requestId): Notification
    {
        return $this->notify(
            $student,
            'request_submitted',
            'Request Submitted',
            'Your transport subscription request has been submitted and is pending review.',
            "/student/transport/my-requests"
        );
    }

    /**
     * Sends a notification to the student confirming their request approval.
     */
    public function notifyRequestApproved(User $student, int $subscriptionId): Notification
    {
        return $this->notify(
            $student,
            'request_approved',
            'Request Approved',
            'Your transport subscription request has been approved! Your subscription is now active.',
            "/student/transport/my-subscription"
        );
    }

    /**
     * Sends a notification to the student informing them of request rejection.
     */
    public function notifyRequestRejected(User $student, string $reason): Notification
    {
        return $this->notify(
            $student,
            'request_rejected',
            'Request Rejected',
            "Your transport subscription request has been rejected. Reason: {$reason}",
            "/student/transport/my-requests"
        );
    }

    /**
     * Sends a notification to the student informing them they have been waitlisted.
     */
    public function notifyRequestWaitlisted(User $student): Notification
    {
        return $this->notify(
            $student,
            'request_waitlisted',
            'Request Waitlisted',
            'Your transport subscription request has been approved but you are on the waitlist due to capacity limits.',
            "/student/transport/my-subscription"
        );
    }

    /**
     * Alerts the student that their subscription is expiring soon.
     */
    public function notifySubscriptionExpiring(User $student, int $daysRemaining): Notification
    {
        return $this->notify(
            $student,
            'subscription_expiring',
            'Subscription Expiring Soon',
            "Your transport subscription will expire in {$daysRemaining} days. Consider renewing to continue service.",
            "/student/transport/my-subscription"
        );
    }

    /**
     * Alerts the student that their transport payment proof has been flagged.
     */
    public function notifyTransportPaymentFlagged(User $student, int $requestId, string $reason): Notification
    {
        return $this->notify(
            $student,
            'transport_payment_flagged',
            'Payment Flagged',
            "Your transport payment proof has been flagged. Reason: {$reason}. Please review and update.",
            "/student/transport/my-requests"
        );
    }

    /**
     * Alerts admins that a payment proof has been flagged for review.
     */
    public function notifyPaymentFlagged(User $admin, int $requestId, string $studentName): Notification
    {
        return $this->notify(
            $admin,
            'payment_flagged',
            'Payment Flagged',
            "A payment proof from {$studentName} has been flagged for review.",
            "/admin/transport/requests/{$requestId}"
        );
    }

    /**
     * Alerts the student that their transport payment proof has been verified.
     */
    public function notifyTransportPaymentVerified(User $student, int $requestId): Notification
    {
        return $this->notify(
            $student,
            'transport_payment_verified',
            'Payment Verified',
            'Your transport payment proof has been verified. You will be notified once the request is approved.',
            "/student/transport/my-requests"
        );
    }



    /**
     * Sends a notification to the student confirming their ID card request submission.
     */
    public function notifyIdCardRequestSubmitted(User $student, int $requestId): Notification
    {
        return $this->notify(
            $student,
            'id_card_request_submitted',
            'ID Card Request Submitted',
            'Your ID card request has been submitted and is pending review.',
            "/student/id-card/my-requests"
        );
    }

    /**
     * Sends a notification to the student confirming their ID card request approval.
     */
    public function notifyIdCardRequestApproved(User $student, int $requestId): Notification
    {
        return $this->notify(
            $student,
            'id_card_request_approved',
            'ID Card Request Approved',
            'Your ID card request has been approved and is being processed.',
            "/student/id-card/my-requests"
        );
    }

    /**
     * Sends a notification to the student informing them of ID card request rejection.
     */
    public function notifyIdCardRequestRejected(User $student, int $requestId, string $reason): Notification
    {
        return $this->notify(
            $student,
            'id_card_request_rejected',
            'ID Card Request Rejected',
            "Your ID card request has been rejected. Reason: {$reason}",
            "/student/id-card/my-requests"
        );
    }

    /**
     * Alerts the student that their ID card is ready for pickup.
     */
    public function notifyIdCardReadyForPickup(User $student, int $requestId): Notification
    {
        return $this->notify(
            $student,
            'id_card_ready',
            'ID Card Ready',
            'Your ID card is ready for pickup. Please visit the student affairs office.',
            "/student/id-card/my-requests"
        );
    }
    /**
     * Alerts the student that their ID card payment proof has been flagged.
     */
    public function notifyIdCardPaymentFlagged(User $student, int $requestId, string $reason): Notification
    {
        return $this->notify(
            $student,
            'id_card_payment_flagged',
            'Payment Flagged',
            "Your ID card payment proof has been flagged. Reason: {$reason}. Please review and update if necessary.",
            "/student/id-card/my-requests"
        );
    }
    /**
     * Confirms to the student that their ID card has been delivered.
     */
    public function notifyIdCardDelivered(User $student, int $requestId): Notification
    {
        return $this->notify(
            $student,
            'id_card_delivered',
            'ID Card Delivered',
            'Your ID card has been marked as delivered. Thank you using our service!',
            "/student/id-card/my-requests"
        );
    }
}
