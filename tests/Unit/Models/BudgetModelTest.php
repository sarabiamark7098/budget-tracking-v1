<?php

namespace Tests\Unit\Models;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_percentage_calculation(): void
    {
        $user   = $this->createUser();
        $btId   = $this->getBT($user)->id;
        $start  = now()->startOfMonth()->format('Y-m-d');

        $budget = Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Food Budget',
            'amount'             => 10000,
            'period'             => 'monthly',
            'start_date'         => $start,
        ]);

        // Expense linked directly to the budget so spent_amount = 3000
        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'budget_id'          => $budget->id,
            'title'              => 'Groceries',
            'amount'             => 3000,
            'spent_at'           => now()->format('Y-m-d'),
        ]);

        // total_budget = 10000 * 1 period (started this month)
        $this->assertEquals(30.0, $budget->fresh()->usage_percentage);
    }

    public function test_remaining_amount_calculation(): void
    {
        $user  = $this->createUser();
        $btId  = $this->getBT($user)->id;
        $start = now()->startOfMonth()->format('Y-m-d');

        $budget = Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Monthly Budget',
            'amount'             => 10000,
            'period'             => 'monthly',
            'start_date'         => $start,
        ]);

        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'budget_id'          => $budget->id,
            'title'              => 'Expense 1',
            'amount'             => 4000,
            'spent_at'           => now()->format('Y-m-d'),
        ]);

        $this->assertEquals(6000.0, $budget->fresh()->remaining_amount);
    }

    public function test_spent_amount_calculation(): void
    {
        $user  = $this->createUser();
        $btId  = $this->getBT($user)->id;
        $start = now()->startOfMonth()->format('Y-m-d');

        $budget = Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Test Budget',
            'amount'             => 20000,
            'period'             => 'monthly',
            'start_date'         => $start,
        ]);

        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'budget_id'          => $budget->id,
            'title'              => 'Expense A',
            'amount'             => 5000,
            'spent_at'           => now()->format('Y-m-d'),
        ]);

        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'budget_id'          => $budget->id,
            'title'              => 'Expense B',
            'amount'             => 3000,
            'spent_at'           => now()->format('Y-m-d'),
        ]);

        $this->assertEquals(8000.0, $budget->fresh()->spent_amount);
    }

    public function test_usage_percentage_zero_when_no_expenses(): void
    {
        $user  = $this->createUser();
        $btId  = $this->getBT($user)->id;
        $start = now()->startOfMonth()->format('Y-m-d');

        $budget = Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Empty Budget',
            'amount'             => 10000,
            'period'             => 'monthly',
            'start_date'         => $start,
        ]);

        $this->assertEquals(0.0, $budget->usage_percentage);
    }

    public function test_remaining_amount_equals_full_amount_when_no_expenses(): void
    {
        $user  = $this->createUser();
        $btId  = $this->getBT($user)->id;
        $start = now()->startOfMonth()->format('Y-m-d');

        $budget = Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'New Budget',
            'amount'             => 15000,
            'period'             => 'monthly',
            'start_date'         => $start,
        ]);

        $this->assertEquals(15000.0, $budget->remaining_amount);
    }
}
