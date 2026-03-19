<?php

namespace Tests\Unit\Models;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_percentage_calculation(): void
    {
        $user = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $user->id,
            'name' => 'Food Budget',
            'amount' => 10000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        Expense::create([
            'user_id' => $user->id,
            'title' => 'Groceries',
            'amount' => 3000,
            'spent_at' => '2024-01-15',
        ]);

        $this->assertEquals(30.0, $budget->usage_percentage);
    }

    public function test_remaining_amount_calculation(): void
    {
        $user = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $user->id,
            'name' => 'Monthly Budget',
            'amount' => 10000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        Expense::create([
            'user_id' => $user->id,
            'title' => 'Expense 1',
            'amount' => 4000,
            'spent_at' => '2024-01-10',
        ]);

        $this->assertEquals(6000.0, $budget->remaining_amount);
    }

    public function test_spent_amount_calculation(): void
    {
        $user = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $user->id,
            'name' => 'Test Budget',
            'amount' => 20000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        Expense::create([
            'user_id' => $user->id,
            'title' => 'Expense A',
            'amount' => 5000,
            'spent_at' => '2024-01-05',
        ]);

        Expense::create([
            'user_id' => $user->id,
            'title' => 'Expense B',
            'amount' => 3000,
            'spent_at' => '2024-01-15',
        ]);

        $this->assertEquals(8000.0, $budget->spent_amount);
    }

    public function test_usage_percentage_zero_when_no_expenses(): void
    {
        $user = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $user->id,
            'name' => 'Empty Budget',
            'amount' => 10000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->assertEquals(0.0, $budget->usage_percentage);
    }

    public function test_remaining_amount_equals_full_amount_when_no_expenses(): void
    {
        $user = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $user->id,
            'name' => 'New Budget',
            'amount' => 15000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->assertEquals(15000.0, $budget->remaining_amount);
    }
}
