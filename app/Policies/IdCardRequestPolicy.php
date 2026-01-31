<?php

namespace App\Policies;

use App\Models\IdCardRequest;
use App\Models\User;

class IdCardRequestPolicy
    {
        /**
         * Determine whether the user can view any requests.
         * Admins can view all; students can view their own.
         */
        public function viewAny(User $user): bool
        {
            return $user->hasRole('admin') || $user->hasRole('student');
        }

        /**
         * Determine whether the user can view the request.
         */
        public function view(User $user, IdCardRequest $request): bool
        {
            // Admins can view any request
            if ($user->hasRole('admin')) {
                return true;
            }

            // Students can only view their own requests
            return $user->id === $request->user_id;
        }

        /**
         * Determine whether the user can create requests.
         */
        public function create(User $user): bool
        {
            return $user->hasRole('student');
        }

        /**
         * Determine whether the user can update the request.
         * Only admins can update (workflow actions).
         */
        public function update(User $user, IdCardRequest $request): bool
        {
            return $user->hasRole('admin');
        }

        /**
         * Determine whether the user can delete the request.
         * Generally not allowed.
         */
        public function delete(User $user, IdCardRequest $request): bool
        {
            return false;
        }

        /**
         * Determine whether the user can verify payment.
         */
        public function verifyPayment(User $user, IdCardRequest $request): bool
        {
            return $user->hasRole('admin') && $request->canVerifyPayment();
        }

        /**
         * Determine whether the user can flag payment.
         */
        public function flagPayment(User $user, IdCardRequest $request): bool
        {
            return $user->hasRole('admin') && $request->canFlagPayment();
        }

        /**
         * Determine whether the user can approve the request.
         */
        public function approve(User $user, IdCardRequest $request): bool
        {
            return $user->hasRole('admin') && $request->canBeApproved();
        }

        /**
         * Determine whether the user can reject the request.
         */
        public function reject(User $user, IdCardRequest $request): bool
        {
            return $user->hasRole('admin') && $request->canBeRejected();
        }

        /**
         * Determine whether the user can mark as ready for pickup.
         */
        public function ready(User $user, IdCardRequest $request): bool
        {
            return $user->hasRole('admin') && $request->canBeReadied();
        }

        /**
         * Determine whether the user can mark as delivered.
         */
        public function deliver(User $user, IdCardRequest $request): bool
        {
            return $user->hasRole('admin') && $request->canBeDelivered();
        }

        /**
         * Determine whether the user can view attachments.
         */
        public function viewAttachments(User $user, IdCardRequest $request): bool
        {
            return $user->hasRole('admin');
        }
    }
