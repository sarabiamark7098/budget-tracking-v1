<?php

namespace Tests\Feature\Budget;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_budget(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/budgets', [
            'name' => 'Monthly Food Budget',
            'amount' => 10000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'alert_threshold' => 80,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Monthly Food Budget');
    }

    public function test_budget_validation_requires_amount(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/budgets', [
            'name' => 'Budget without amount',
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_can_list_budgets(): void
    {
        $user = User::factory()->create();
        Budget::create([
            'user_id' => $user->id,
            'name' => 'Test Budget',
            'amount' => 10000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/budgets');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_budget_summary_returns_spent_remaining_percentage(): void
    {
        $user = User::factory()->create();
        Budget::create([
            'user_id' => $user->id,
            'name' => 'Food Budget',
            'amount' => 10000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/budgets/summary');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_cannot_access_other_users_budget(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Budget',
            'amount' => 5000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/budgets/{$budget->id}");
        $response->assertStatus(403);
    }

    public function test_can_show_budget_with_computed_attributes(): void
    {
        $user = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $user->id,
            'name' => 'Test Budget',
            'amount' => 10000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/budgets/{$budget->id}");
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'name', 'amount', 'spent_amount', 'remaining_amount', 'usage_percentage']]);
    }

    public function test_can_update_budget(): void
    {
        $user = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'amount' => 5000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/budgets/{$budget->id}", [
            'name' => 'Updated Budget',
            'amount' => 10000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Budget');
    }

    public function test_can_delete_budget(): void
    {
        $user = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $user->id,
            'name' => 'To Delete',
            'amount' => 5000,
            'period' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/budgets/{$budget->id}");
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_budget_validation_requires_period(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/budgets', [
            'name' => 'Budget',
            'amount' => 10000,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }
}
