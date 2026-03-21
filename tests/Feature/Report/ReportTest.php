<?php

namespace Tests\Feature\Report;

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_income_expense_report(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/income-expense');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_income', 'total_expenses', 'net']]);
    }

    public function test_report_calculates_correct_values(): void
    {
        $user = $this->createUser();

        Income::create(['user_id' => $user->id, 'budget_tracking_id' => $this->getBT($user)->id, 'title' => 'Salary', 'amount' => 80000, 'received_at' => now()->format('Y-m-d')]);
        Expense::create(['user_id' => $user->id, 'budget_tracking_id' => $this->getBT($user)->id, 'title' => 'Rent', 'amount' => 20000, 'spent_at' => now()->format('Y-m-d'), 'is_recurring' => false]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/income-expense');
        $response->assertOk();
        $this->assertEquals(80000, $response->json('data.total_income'));
        $this->assertEquals(20000, $response->json('data.total_expenses'));
        $this->assertEquals(60000, $response->json('data.net'));
    }

    public function test_report_filters_by_date_range(): void
    {
        $user = $this->createUser();

        Income::create(['user_id' => $user->id, 'budget_tracking_id' => $this->getBT($user)->id, 'title' => 'Jan Income', 'amount' => 50000, 'received_at' => '2024-01-15']);
        Income::create(['user_id' => $user->id, 'budget_tracking_id' => $this->getBT($user)->id, 'title' => 'Aug Income', 'amount' => 60000, 'received_at' => '2024-08-15']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/income-expense?date_from=2024-01-01&date_to=2024-03-31');
        $response->assertOk();
        $this->assertEquals(50000, $response->json('data.total_income'));
    }

    public function test_can_get_net_worth_report(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/net-worth');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_assets', 'total_liabilities', 'net_worth']]);
    }

    public function test_can_export_csv(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/export/csv?type=income');
        $response->assertOk();
    }

    public function test_report_only_shows_own_data(): void
    {
        $user  = $this->createUser();
        $other = $this->createUser();

        Income::create(['user_id' => $other->id, 'budget_tracking_id' => $this->getBT($other)->id, 'title' => 'Other Income', 'amount' => 100000, 'received_at' => '2024-01-01']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/income-expense');
        $response->assertOk();
        $this->assertEquals(0.0, $response->json('data.total_income'));
    }

    public function test_report_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/reports/income-expense');
        $response->assertStatus(401);
    }
}
