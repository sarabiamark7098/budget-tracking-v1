<?php

namespace App\Policies;

use App\Models\User;

/**
 * Shared helper for all BudgetTracking-scoped policies (S-04).
 *
 * All financial models are scoped to a single BudgetTracking. A user may
 * view / modify a record only when that record belongs to the same
 * BudgetTracking the user is a member of.
 *
 * getBudgetTrackingId() is static-cached per user per PHP request so that
 * the membership query runs at most once regardless of how many policy
 * checks are performed in the same request.
 */
abstract class BasePolicy
{
    /**
     * Return the user's active BudgetTracking ID, cached for the request.
     */
    protected function getBudgetTrackingId(User $user): ?int
    {
        static $cache = [];

        if (!array_key_exists($user->id, $cache)) {
            $cache[$user->id] = $user->budgetTracking()?->id;
        }

        return $cache[$user->id];
    }

    /**
     * Check that a model belongs to the user's BudgetTracking.
     */
    protected function ownedByTracker(User $user, mixed $model): bool
    {
        $btId = $this->getBudgetTrackingId($user);
        return $btId !== null && $btId === (int) $model->budget_tracking_id;
    }
}
