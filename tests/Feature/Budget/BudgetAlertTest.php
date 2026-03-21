<?php

namespace Tests\Feature\Budget;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_usage_percentage_is_accurate(): void
    {
        $user   = $this->createUser();
        $bt     = $this->getBT($user);
        $start  = now()->startOfMonth()->toDateString();

        $budget = Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $bt->id,
            'name'               => 'Test Budget',
            'amount'             => 10000,
            'period'             => 'monthly',
            'start_date'         => $start,
        ]);

        // Link the expense directly to this budget so spent_amount uses the explicit path
        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $bt->id,
            'budget_id'          => $budget->id,
            'title'              => 'Expense',
            'amount'             => 5000,
            'spent_at'           => now()->toDateString(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/budgets/{$budget->id}");
        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(50.0, $data['usage_percentage']);
    }

    public function test_can_create_budget(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/budgets', [
            'name'       => 'Custom Budget',
            'amount'     => 20000,
            'period'     => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Custom Budget');
    }

    public function test_budget_remaining_amount_is_correct(): void
    {
        $user  = $this->createUser();
        $bt    = $this->getBT($user);
        $start = now()->startOfMonth()->toDateString();

        $budget = Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $bt->id,
            'name'               => 'Test Budget',
            'amount'             => 20000,
            'period'             => 'monthly',
            'start_date'         => $start,
        ]);

        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $bt->id,
            'budget_id'          => $budget->id,
            'title'              => 'Expense',
            'amount'             => 8000,
            'spent_at'           => now()->toDateString(),
        ]);

        $budget->refresh();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/budgets/{$budget->id}");
        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(12000.0, $data['remaining_amount']);
    }
}
