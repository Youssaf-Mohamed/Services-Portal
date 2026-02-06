<?php

namespace App\Policies;

use App\Models\IdCardRequest;
use App\Models\User;

/**
 * Policy for IdCardRequest with status gating.
 */
class IdCardRequestPolicy
{
    /**
     * Statuses that allow soft deletion.
     */
    private const DELETABLE_STATUSES = ['pending', 'rejected', 'cancelled'];

    /**
     * Determine if the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('student') || $user->can('idcard.requests.view');
    }

    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, IdCardRequest $idCardRequest): bool
    {
        return $user->id === $idCardRequest->user_id || $user->can('idcard.requests.view');
    }

    /**
     * Determine if the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    /**
     * Determine if the request can be soft deleted.
     */
    public function delete(User $user, IdCardRequest $idCardRequest): bool
    {
        if (!$user->can('idcard.requests.delete')) {
            return false;
        }

        // Get status value (handle enum cast)
        $status = $idCardRequest->status instanceof \BackedEnum 
            ? $idCardRequest->status->value 
            : $idCardRequest->status;

        return in_array($status, self::DELETABLE_STATUSES);
    }

    /**
     * Determine if the request can be restored.
     */
    public function restore(User $user, IdCardRequest $idCardRequest): bool
    {
        if (!$user->can('idcard.requests.delete')) {
            return false;
        }

        return $idCardRequest->trashed();
    }

    /**
     * Determine if the request can be permanently deleted.
     */
    public function forceDelete(User $user, IdCardRequest $idCardRequest): bool
    {
        if (!$user->can('system.data.purge')) {
            return false;
        }

        return $idCardRequest->trashed();
    }
}
