<?php

namespace Tests\Feature\Investment;

use App\Models\Investment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_investment(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/investments', [
            'name'            => 'Apple Stocks',
            'type'            => 'stocks',
            'amount_invested' => 100000,
            'current_value'   => 120000,
            'purchase_date'   => '2024-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Apple Stocks');
    }

    public function test_can_get_portfolio_summary(): void
    {
        $user = $this->createUser();
        Investment::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'name'               => 'Stock Portfolio',
            'type'               => 'stocks',
            'amount_invested'    => 100000,
            'current_value'      => 120000,
            'purchase_date'      => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/investments/portfolio');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_portfolio_includes_roi(): void
    {
        $user = $this->createUser();
        Investment::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'name'               => 'Test Investment',
            'type'               => 'mutual_fund',
            'amount_invested'    => 100000,
            'current_value'      => 120000,
            'purchase_date'      => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/investments');
        $response->assertOk();
        $investments = $response->json('data.data');
        $this->assertNotEmpty($investments);
        $this->assertArrayHasKey('roi', $investments[0]);
    }

    public function test_can_update_investment(): void
    {
        $user = $this->createUser();
        $investment = Investment::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'name'               => 'Old Investment',
            'type'               => 'stocks',
            'amount_invested'    => 50000,
            'current_value'      => 55000,
            'purchase_date'      => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/investments/{$investment->id}", [
            'name'            => 'Updated Investment',
            'type'            => 'stocks',
            'amount_invested' => 50000,
            'current_value'   => 70000,
            'purchase_date'   => '2024-01-01',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Investment');
    }

    public function test_can_delete_investment(): void
    {
        $user = $this->createUser();
        $investment = Investment::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'name'               => 'To Delete',
            'type'               => 'other',
            'amount_invested'    => 10000,
            'current_value'      => 11000,
            'purchase_date'      => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/investments/{$investment->id}");
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_investment_validation_requires_type(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/investments', [
            'name'            => 'No Type',
            'amount_invested' => 10000,
            'current_value'   => 12000,
            'purchase_date'   => '2024-01-01',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_cannot_access_other_users_investment(): void
    {
        $user      = $this->createUser();
        $otherUser = $this->createUser();
        $investment = Investment::create([
            'user_id'            => $otherUser->id,
            'budget_tracking_id' => $this->getBT($otherUser)->id,
            'name'               => 'Other Investment',
            'type'               => 'stocks',
            'amount_invested'    => 50000,
            'current_value'      => 60000,
            'purchase_date'      => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/investments/{$investment->id}");
        $response->assertStatus(403);
    }
}
