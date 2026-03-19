<?php

namespace Tests\Feature\MP2;

use App\Models\MP2Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MP2Test extends TestCase
{
    use RefreshDatabase;

    public function test_can_calculate_mp2_without_authentication(): void
    {
        $response = $this->postJson('/api/v1/mp2/calculate', [
            'monthly_contribution' => 1000,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [
                'total_contributions',
                'projected_earnings',
                'total_value',
                'yearly_breakdown',
            ]]);
    }

    public function test_calculation_returns_correct_total_contributions(): void
    {
        $response = $this->postJson('/api/v1/mp2/calculate', [
            'monthly_contribution' => 2000,
            'duration_years' => 3,
            'start_date' => '2024-01-01',
        ]);

        $response->assertOk();
        $this->assertEquals(72000, $response->json('data.total_contributions'));
    }

    public function test_calculation_projected_earnings_exceed_contributions(): void
    {
        $response = $this->postJson('/api/v1/mp2/calculate', [
            'monthly_contribution' => 5000,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
        ]);

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThan($data['total_contributions'], $data['total_value']);
    }

    public function test_calculation_yearly_breakdown_count_matches_duration(): void
    {
        $response = $this->postJson('/api/v1/mp2/calculate', [
            'monthly_contribution' => 1000,
            'duration_years' => 7,
            'start_date' => '2024-01-01',
        ]);

        $response->assertOk();
        $this->assertCount(7, $response->json('data.yearly_breakdown'));
    }

    public function test_can_save_mp2_plan(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/mp2-plans', [
            'name' => 'Retirement MP2',
            'monthly_contribution' => 3000,
            'duration_years' => 10,
            'start_date' => '2024-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Retirement MP2');
    }

    public function test_can_list_mp2_plans(): void
    {
        $user = User::factory()->create();
        MP2Plan::create([
            'user_id' => $user->id,
            'name' => 'My MP2',
            'monthly_contribution' => 2000,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
            'projected_earnings' => 50000,
            'total_contributions' => 120000,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/mp2-plans');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_can_update_mp2_plan(): void
    {
        $user = User::factory()->create();
        $plan = MP2Plan::create([
            'user_id' => $user->id,
            'name' => 'Old MP2',
            'monthly_contribution' => 1000,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
            'projected_earnings' => 20000,
            'total_contributions' => 60000,
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/mp2-plans/{$plan->id}", [
            'name' => 'Updated MP2',
            'monthly_contribution' => 2000,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated MP2');
    }

    public function test_can_delete_mp2_plan(): void
    {
        $user = User::factory()->create();
        $plan = MP2Plan::create([
            'user_id' => $user->id,
            'name' => 'To Delete',
            'monthly_contribution' => 1000,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
            'projected_earnings' => 20000,
            'total_contributions' => 60000,
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/mp2-plans/{$plan->id}");
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted('mp2_plans', ['id' => $plan->id]);
    }

    public function test_mp2_plans_only_shows_own_records(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        MP2Plan::create(['user_id' => $user->id, 'name' => 'Mine', 'monthly_contribution' => 1000, 'duration_years' => 5, 'start_date' => '2024-01-01', 'projected_earnings' => 0, 'total_contributions' => 0]);
        MP2Plan::create(['user_id' => $other->id, 'name' => 'Theirs', 'monthly_contribution' => 1000, 'duration_years' => 5, 'start_date' => '2024-01-01', 'projected_earnings' => 0, 'total_contributions' => 0]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/mp2-plans');
        $response->assertOk();
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
    }
}
