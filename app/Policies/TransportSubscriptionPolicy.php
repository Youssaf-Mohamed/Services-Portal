<?php

namespace App\Policies;

use App\Models\TransportSubscription;
use App\Models\User;

/**
 * Policy for TransportSubscription with status gating.
 */
class TransportSubscriptionPolicy
{
    /**
     * Statuses that allow cancellation/deletion.
     */
    private const DELETABLE_STATUSES = ['active', 'waitlisted', 'cancelled'];

    /**
     * Determine if the subscription can be cancelled/deleted.
     */
    public function delete(User $user, TransportSubscription $subscription): bool
    {
        if (!$user->can('transport.subscriptions.delete')) {
            return false;
        }

        return in_array($subscription->status, self::DELETABLE_STATUSES);
    }

    /**
     * Determine if the subscription can be restored.
     */
    public function restore(User $user, TransportSubscription $subscription): bool
    {
        if (!$user->can('transport.subscriptions.delete')) {
            return false;
        }

        return $subscription->trashed();
    }

    /**
     * Determine if the subscription can be permanently deleted.
     */
    public function forceDelete(User $user, TransportSubscription $subscription): bool
    {
        if (!$user->can('system.data.purge')) {
            return false;
        }

        return $subscription->trashed();
    }
}
