<?php

namespace Tests\Feature\Insurance;

use App\Models\InsurancePlan;
use App\Models\InsurancePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsurancePlanTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(User $user, array $overrides = []): InsurancePlan
    {
        return InsurancePlan::create(array_merge([
            'user_id' => $user->id,
            'provider_name' => 'Sun Life',
            'plan_name' => 'Life Insurance',
            'coverage_type' => 'Life',
            'coverage_amount' => 1000000,
            'premium_amount' => 5000,
            'payment_frequency' => 'monthly',
            'next_payment_date' => '2024-02-01',
        ], $overrides));
    }

    public function test_can_create_insurance_plan(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/insurance-plans', [
            'provider_name' => 'AXA',
            'plan_name' => 'Health Shield',
            'coverage_type' => 'Health',
            'coverage_amount' => 500000,
            'premium_amount' => 2500,
            'payment_frequency' => 'monthly',
            'next_payment_date' => '2024-02-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.plan_name', 'Health Shield');
    }

    public function test_can_list_insurance_plans(): void
    {
        $user = User::factory()->create();
        $this->makePlan($user);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/insurance-plans');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_cannot_access_other_users_insurance_plan(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $plan = $this->makePlan($other);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/insurance-plans/{$plan->id}");
        $response->assertStatus(403);
    }

    public function test_can_update_insurance_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan($user);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/insurance-plans/{$plan->id}", [
            'provider_name' => 'Manulife',
            'plan_name' => 'Updated Plan',
            'coverage_type' => 'Life',
            'coverage_amount' => 2000000,
            'premium_amount' => 6000,
            'payment_frequency' => 'annually',
            'next_payment_date' => '2025-01-01',
        ]);

        $response->assertOk()->assertJsonPath('data.plan_name', 'Updated Plan');
    }

    public function test_can_delete_insurance_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan($user);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/insurance-plans/{$plan->id}");
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted('insurance_plans', ['id' => $plan->id]);
    }

    public function test_can_record_insurance_payment(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan($user);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/insurance-payments', [
            'insurance_plan_id' => $plan->id,
            'amount' => 5000,
            'payment_date' => '2024-01-01',
            'note' => 'January payment',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('insurance_payments', ['insurance_plan_id' => $plan->id, 'amount' => 5000]);
    }

    public function test_can_list_insurance_payments(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan($user);
        InsurancePayment::create([
            'user_id' => $user->id,
            'insurance_plan_id' => $plan->id,
            'amount' => 5000,
            'payment_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/insurance-payments');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_insurance_plan_creation_requires_provider(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/insurance-plans', [
            'plan_name' => 'Missing Provider',
            'coverage_amount' => 500000,
        ]);
        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_insurance_plans_only_shows_own_records(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->makePlan($user);
        $this->makePlan($other);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/insurance-plans');
        $response->assertOk();
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
    }
}
