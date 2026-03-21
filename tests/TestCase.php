<?php

namespace Tests;

use App\Models\BudgetTracking;
use App\Models\BudgetTrackingMember;
use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user with an active BudgetTracking (required by most protected routes).
     */
    protected function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        $budget = BudgetTracking::create([
            'owner_id'   => $user->id,
            'name'       => $user->name . "'s Budget",
            'currency'   => 'PHP',
            'period'     => 'monthly',
            'start_date' => now()->startOfMonth(),
            'join_code'  => BudgetTracking::generateJoinCode(),
            'status'     => 'active',
        ]);

        BudgetTrackingMember::create([
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
            'role'               => 'owner',
            'joined_at'          => now(),
        ]);

        return $user;
    }

    /**
     * Get the BudgetTracking for a user (must have been created via createUser()).
     */
    protected function getBT(User $user): BudgetTracking
    {
        return $user->budgetTracking();
    }

    /**
     * Seed income for a user's budget tracker so availableBalance() > 0.
     */
    protected function addIncome(User $user, float $amount = 100000): Income
    {
        return Income::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'title'              => 'Test Income',
            'amount'             => $amount,
            'received_at'        => now()->toDateString(),
        ]);
    }
}
