<?php

namespace Tests\Feature\Plan;

use App\Models\FinancialGoal;
use App\Models\FinancialPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_financial_plan(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-plans', [
            'name' => 'Retirement Plan',
            'description' => 'Long-term retirement savings',
            'monthly_income_target' => 100000,
            'monthly_expense_limit' => 50000,
            'savings_target' => 5000000,
            'start_date' => '2024-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Retirement Plan');
    }

    public function test_can_list_financial_plans(): void
    {
        $user = User::factory()->create();
        FinancialPlan::create([
            'user_id' => $user->id,
            'name' => 'Plan A',
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/financial-plans');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_cannot_access_other_users_plan(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $plan = FinancialPlan::create([
            'user_id' => $other->id,
            'name' => 'Other Plan',
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/financial-plans/{$plan->id}");
        $response->assertStatus(403);
    }

    public function test_can_update_financial_plan(): void
    {
        $user = User::factory()->create();
        $plan = FinancialPlan::create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/financial-plans/{$plan->id}", [
            'name' => 'Updated Plan',
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Plan');
    }

    public function test_can_delete_financial_plan(): void
    {
        $user = User::factory()->create();
        $plan = FinancialPlan::create([
            'user_id' => $user->id,
            'name' => 'To Delete',
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/financial-plans/{$plan->id}");
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted('financial_plans', ['id' => $plan->id]);
    }

    public function test_can_add_goal_to_plan(): void
    {
        $user = User::factory()->create();
        $plan = FinancialPlan::create([
            'user_id' => $user->id,
            'name' => 'Plan',
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-goals', [
            'financial_plan_id' => $plan->id,
            'name' => 'Emergency Fund',
            'target_amount' => 100000,
            'priority' => 'high',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Emergency Fund')
            ->assertJsonPath('data.target_amount', '100000.00');
    }

    public function test_can_update_goal_progress(): void
    {
        $user = User::factory()->create();
        $goal = FinancialGoal::create([
            'user_id' => $user->id,
            'name' => 'Vacation Fund',
            'target_amount' => 50000,
            'current_amount' => 0,
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/financial-goals/{$goal->id}/progress", [
            'amount' => 20000,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('financial_goals', [
            'id' => $goal->id,
            'current_amount' => 20000,
        ]);
    }

    public function test_goal_progress_percentage_is_calculated(): void
    {
        $user = User::factory()->create();
        $goal = FinancialGoal::create([
            'user_id' => $user->id,
            'name' => 'Car Fund',
            'target_amount' => 200000,
            'current_amount' => 50000,
            'priority' => 'medium',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/financial-goals/{$goal->id}");
        $response->assertOk();
        $this->assertEquals(25.0, $response->json('data.progress_percentage'));
    }

    public function test_plan_creation_requires_name(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/financial-plans', [
            'start_date' => '2024-01-01',
        ]);
        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_plans_only_shows_own_records(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        FinancialPlan::create(['user_id' => $user->id, 'name' => 'My Plan', 'start_date' => '2024-01-01', 'status' => 'active']);
        FinancialPlan::create(['user_id' => $other->id, 'name' => 'Other Plan', 'start_date' => '2024-01-01', 'status' => 'active']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/financial-plans');
        $response->assertOk();
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
    }
}
