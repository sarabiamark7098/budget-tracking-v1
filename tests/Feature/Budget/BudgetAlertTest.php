<?php

namespace Tests\Feature\Budget;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_usage_percentage_is_accurate(): void
    {
        $user = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $user->id,
            'name' => 'Test Budget',
            'amount' => 10000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'alert_threshold' => 80,
        ]);

        Expense::create([
            'user_id' => $user->id,
            'title' => 'Expense',
            'amount' => 5000,
            'spent_at' => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/budgets/{$budget->id}");
        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(50.0, $data['usage_percentage']);
    }

    public function test_can_create_budget_with_custom_threshold(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/budgets', [
            'name' => 'Custom Threshold Budget',
            'amount' => 20000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'alert_threshold' => 75,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.alert_threshold', 75);
    }

    public function test_budget_remaining_amount_is_correct(): void
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
            'title' => 'Expense',
            'amount' => 8000,
            'spent_at' => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/budgets/{$budget->id}");
        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(12000.0, $data['remaining_amount']);
    }

    public function test_alert_threshold_validation(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/budgets', [
            'name' => 'Invalid Threshold',
            'amount' => 10000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'alert_threshold' => 150, // Invalid: must be <= 100
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }
}
