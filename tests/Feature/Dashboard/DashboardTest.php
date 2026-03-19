<?php

namespace Tests\Feature\Dashboard;

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_summary(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [
                'total_income',
                'total_expenses',
                'balance',
                'total_debt',
                'total_investments',
            ]]);
    }

    public function test_dashboard_includes_correct_totals(): void
    {
        $user = User::factory()->create();

        Income::create([
            'user_id' => $user->id,
            'title' => 'Salary',
            'amount' => 50000,
            'received_at' => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);
        Expense::create([
            'user_id' => $user->id,
            'title' => 'Rent',
            'amount' => 15000,
            'spent_at' => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals(50000, $data['total_income']);
        $this->assertEquals(15000, $data['total_expenses']);
        $this->assertEquals(35000, $data['balance']);
    }

    public function test_dashboard_includes_monthly_data(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['monthly_data']]);
    }

    public function test_dashboard_includes_recent_transactions(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['recent_transactions']]);
    }

    public function test_dashboard_only_shows_own_data(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Income::create(['user_id' => $other->id, 'title' => 'Other Income', 'amount' => 999999, 'received_at' => now()->format('Y-m-d'), 'is_recurring' => false]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();
        $this->assertEquals(0, $response->json('data.total_income'));
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/dashboard');
        $response->assertStatus(401);
    }

    public function test_dashboard_accepts_date_range_filter(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard?date_from=2024-01-01&date_to=2024-12-31');
        $response->assertOk()->assertJsonPath('success', true);
    }
}
