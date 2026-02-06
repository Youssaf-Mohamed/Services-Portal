<?php

namespace App\Policies;

use App\Models\TransportSubscriptionRequest;
use App\Models\User;

/**
 * Policy for TransportSubscriptionRequest with status gating.
 */
class TransportSubscriptionRequestPolicy
{
    /**
     * Statuses that allow soft deletion.
     */
    private const DELETABLE_STATUSES = ['pending', 'rejected', 'cancelled'];

    /**
     * Determine if the request can be soft deleted.
     */
    public function delete(User $user, TransportSubscriptionRequest $subscriptionRequest): bool
    {
        // Must have delete permission
        if (!$user->can('transport.requests.delete')) {
            return false;
        }

        // Status gating: only allow deletion of specific statuses
        return in_array($subscriptionRequest->status, self::DELETABLE_STATUSES);
    }

    /**
     * Determine if the request can be restored.
     */
    public function restore(User $user, TransportSubscriptionRequest $subscriptionRequest): bool
    {
        // Must have delete permission (restore is part of delete workflow)
        if (!$user->can('transport.requests.delete')) {
            return false;
        }

        // Must be soft deleted
        return $subscriptionRequest->trashed();
    }

    /**
     * Determine if the request can be permanently deleted.
     */
    public function forceDelete(User $user, TransportSubscriptionRequest $subscriptionRequest): bool
    {
        // Only system.data.purge can force delete
        if (!$user->can('system.data.purge')) {
            return false;
        }

        // Must be soft deleted first
        return $subscriptionRequest->trashed();
    }
}
