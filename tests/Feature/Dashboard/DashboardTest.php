<?php

namespace Tests\Feature\Dashboard;

use App\Models\Budget;
use App\Models\BudgetTracking;
use App\Models\BudgetTrackingAllocation;
use App\Models\BudgetTrackingMember;
use App\Models\BudgetTrackingTransaction;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    // ─── Existing Tests ───────────────────────────────────────────────────────────

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
                'budget_monitor',
                'budget_list',
                'expense_breakdown',
                'debt_list',
                'month_report',
                'year_report',
            ]]);
    }

    public function test_dashboard_includes_correct_totals(): void
    {
        $user = User::factory()->create();

        Income::create([
            'user_id'      => $user->id,
            'title'        => 'Salary',
            'amount'       => 50000,
            'received_at'  => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);
        Expense::create([
            'user_id'      => $user->id,
            'title'        => 'Rent',
            'amount'       => 15000,
            'spent_at'     => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals(50000, $data['total_income']);
        $this->assertEquals(15000, $data['total_expenses']);
        // balance = income − expenses − debt_payments (no payments in this test → 0)
        $this->assertEquals(35000, $data['balance']);
    }

    public function test_dashboard_includes_monthly_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['monthly_data']]);
    }

    public function test_dashboard_includes_recent_transactions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['recent_transactions']]);
    }

    public function test_dashboard_only_shows_own_data(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        Income::create(['user_id' => $other->id, 'title' => 'Other Income', 'amount' => 999999, 'received_at' => now()->format('Y-m-d'), 'is_recurring' => false]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();
        $this->assertEquals(0, $response->json('data.total_income'));
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/v1/dashboard')->assertStatus(401);
    }

    public function test_dashboard_accepts_date_range_filter(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/dashboard?date_from=2024-01-01&date_to=2024-12-31')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    // ─── Budget Monitor: Personal Budgets ────────────────────────────────────────

    public function test_dashboard_budget_monitor_shows_personal_budgets(): void
    {
        $user = User::factory()->create();

        // Income this month
        Income::create([
            'user_id'      => $user->id,
            'title'        => 'Salary',
            'amount'       => 50000,
            'received_at'  => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);

        // Personal budget for this month
        Budget::create([
            'user_id'         => $user->id,
            'name'            => 'Groceries Budget',
            'amount'          => 10000,
            'period'          => 'monthly',
            'start_date'      => now()->startOfMonth()->toDateString(),
            'end_date'        => now()->endOfMonth()->toDateString(),
            'alert_threshold' => 80,
        ]);

        // Expense within the budget period
        Expense::create([
            'user_id'      => $user->id,
            'title'        => 'Supermarket',
            'amount'       => 6000,
            'spent_at'     => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();

        $personal = $response->json('data.budget_monitor.personal');

        $this->assertEquals(10000, $personal['total_budgeted']);
        $this->assertEquals(6000,  $personal['total_spent']);
        $this->assertEquals(4000,  $personal['total_remaining']);
        $this->assertEquals(60.0,  $personal['usage_pct']);
        $this->assertTrue($personal['within_budget']);
        $this->assertTrue($personal['within_income']);
        $this->assertEquals('on_track', $personal['status']);
        $this->assertCount(1, $personal['budgets']);
    }

    public function test_budget_monitor_flags_warning_when_near_threshold(): void
    {
        $user = User::factory()->create();

        // Income must be higher than spending so over_income doesn't shadow the warning status
        Income::create([
            'user_id'      => $user->id,
            'title'        => 'Salary',
            'amount'       => 20000,
            'received_at'  => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);

        Budget::create([
            'user_id'         => $user->id,
            'name'            => 'Food',
            'amount'          => 5000,
            'period'          => 'monthly',
            'start_date'      => now()->startOfMonth()->toDateString(),
            'end_date'        => now()->endOfMonth()->toDateString(),
            'alert_threshold' => 80,
        ]);

        // 85% of budget spent
        Expense::create([
            'user_id'      => $user->id,
            'title'        => 'Groceries',
            'amount'       => 4250,
            'spent_at'     => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $personal = $response->json('data.budget_monitor.personal');

        $this->assertEquals('warning', $personal['budgets'][0]['status']);
        $this->assertEquals('warning', $personal['status']);
    }

    public function test_budget_monitor_flags_over_budget(): void
    {
        $user = User::factory()->create();

        // Income must be higher than spending so over_income doesn't shadow the over_budget status
        Income::create([
            'user_id'      => $user->id,
            'title'        => 'Salary',
            'amount'       => 20000,
            'received_at'  => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);

        Budget::create([
            'user_id'         => $user->id,
            'name'            => 'Entertainment',
            'amount'          => 2000,
            'period'          => 'monthly',
            'start_date'      => now()->startOfMonth()->toDateString(),
            'end_date'        => now()->endOfMonth()->toDateString(),
            'alert_threshold' => 80,
        ]);

        // 110% of budget spent
        Expense::create([
            'user_id'      => $user->id,
            'title'        => 'Concert tickets',
            'amount'       => 2200,
            'spent_at'     => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $personal = $response->json('data.budget_monitor.personal');

        $this->assertFalse($personal['within_budget']);
        $this->assertEquals('over_budget', $personal['budgets'][0]['status']);
        $this->assertEquals('over_budget', $personal['status']);
    }

    public function test_budget_monitor_flags_over_income(): void
    {
        $user = User::factory()->create();

        // Income: 5000; budget: 8000; spent: 6000 → over income
        Income::create([
            'user_id'      => $user->id,
            'title'        => 'Part-time',
            'amount'       => 5000,
            'received_at'  => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);

        Budget::create([
            'user_id'         => $user->id,
            'name'            => 'Monthly',
            'amount'          => 8000,
            'period'          => 'monthly',
            'start_date'      => now()->startOfMonth()->toDateString(),
            'end_date'        => now()->endOfMonth()->toDateString(),
            'alert_threshold' => 80,
        ]);

        Expense::create([
            'user_id'      => $user->id,
            'title'        => 'Rent + food',
            'amount'       => 6000,
            'spent_at'     => now()->format('Y-m-d'),
            'is_recurring' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $personal = $response->json('data.budget_monitor.personal');

        $this->assertFalse($personal['within_income']);
        $this->assertEquals('over_income', $personal['status']);
        $this->assertEquals(-1000, $personal['income_surplus']);
    }

    public function test_budget_monitor_is_empty_when_no_budgets_set(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');

        $personal = $response->json('data.budget_monitor.personal');

        $this->assertEquals(0, $personal['total_budgeted']);
        $this->assertEquals(0, $personal['total_spent']);
        $this->assertEquals(0, $personal['budget_count']);
        $this->assertEmpty($personal['budgets']);
    }

    // ─── Budget Monitor: Shared Budget Tracking ──────────────────────────────────

    public function test_budget_monitor_shows_null_when_no_budget_tracking(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');

        $this->assertNull($response->json('data.budget_monitor.budget_tracking'));
    }

    public function test_budget_monitor_shows_shared_budget_tracking(): void
    {
        $owner = User::factory()->create();

        $bt = BudgetTracking::create([
            'owner_id'   => $owner->id,
            'name'       => 'Family Budget',
            'currency'   => 'PHP',
            'period'     => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date'   => now()->endOfMonth()->toDateString(),
            'join_code'  => BudgetTracking::generateJoinCode(),
            'status'     => 'active',
        ]);
        BudgetTrackingMember::create([
            'budget_tracking_id' => $bt->id,
            'user_id'            => $owner->id,
            'role'               => 'owner',
            'joined_at'          => now(),
        ]);
        BudgetTrackingAllocation::create([
            'budget_tracking_id' => $bt->id,
            'name'               => 'Groceries',
            'allocated_amount'   => 8000,
            'color'              => '#4CAF50',
        ]);

        // Income and expense inside the budget tracking
        BudgetTrackingTransaction::create([
            'budget_tracking_id' => $bt->id,
            'user_id'            => $owner->id,
            'type'               => 'income',
            'title'              => 'Salary',
            'amount'             => 40000,
            'date'               => now()->format('Y-m-d'),
        ]);
        BudgetTrackingTransaction::create([
            'budget_tracking_id'             => $bt->id,
            'user_id'                        => $owner->id,
            'budget_tracking_allocation_id'  => BudgetTrackingAllocation::first()->id,
            'type'                           => 'expense',
            'title'                          => 'Weekly groceries',
            'amount'                         => 5000,
            'date'                           => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();

        $bt_monitor = $response->json('data.budget_monitor.budget_tracking');

        $this->assertNotNull($bt_monitor);
        $this->assertEquals('Family Budget', $bt_monitor['name']);
        $this->assertEquals(8000,  $bt_monitor['total_allocated']);
        $this->assertEquals(40000, $bt_monitor['total_income']);
        $this->assertEquals(5000,  $bt_monitor['total_expense']);
        $this->assertEquals(3000,  $bt_monitor['remaining_budget']);
        $this->assertEquals(35000, $bt_monitor['income_surplus']);
        $this->assertTrue($bt_monitor['within_allocation']);
        $this->assertTrue($bt_monitor['within_income']);
        $this->assertEquals('on_track', $bt_monitor['status']);
        $this->assertCount(1, $bt_monitor['allocations']);
    }

    public function test_shared_budget_monitor_detects_over_allocation(): void
    {
        $owner = User::factory()->create();

        $bt = BudgetTracking::create([
            'owner_id'   => $owner->id,
            'name'       => 'Tight Budget',
            'currency'   => 'PHP',
            'period'     => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date'   => now()->endOfMonth()->toDateString(),
            'join_code'  => BudgetTracking::generateJoinCode(),
            'status'     => 'active',
        ]);
        BudgetTrackingMember::create([
            'budget_tracking_id' => $bt->id,
            'user_id'            => $owner->id,
            'role'               => 'owner',
            'joined_at'          => now(),
        ]);
        $alloc = BudgetTrackingAllocation::create([
            'budget_tracking_id' => $bt->id,
            'name'               => 'Rent',
            'allocated_amount'   => 3000,
            'color'              => '#red',
        ]);

        // Income recorded inside the budget tracking
        BudgetTrackingTransaction::create([
            'budget_tracking_id' => $bt->id,
            'user_id'            => $owner->id,
            'type'               => 'income',
            'title'              => 'Combined income',
            'amount'             => 20000,
            'date'               => now()->format('Y-m-d'),
        ]);

        // Spend MORE than the allocation
        BudgetTrackingTransaction::create([
            'budget_tracking_id'            => $bt->id,
            'user_id'                       => $owner->id,
            'budget_tracking_allocation_id' => $alloc->id,
            'type'                          => 'expense',
            'title'                         => 'Rent payment',
            'amount'                        => 3500,
            'date'                          => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/dashboard');
        $bt_monitor = $response->json('data.budget_monitor.budget_tracking');

        $this->assertFalse($bt_monitor['within_allocation']);
        $this->assertTrue($bt_monitor['within_income']);   // 3500 < 20000
        $this->assertEquals('over_budget', $bt_monitor['status']);
        $this->assertEquals('over_budget', $bt_monitor['allocations'][0]['status']);
    }

    public function test_shared_budget_member_sees_same_budget_monitor(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();

        $bt = BudgetTracking::create([
            'owner_id'   => $owner->id,
            'name'       => 'Joint Budget',
            'currency'   => 'PHP',
            'period'     => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date'   => now()->endOfMonth()->toDateString(),
            'join_code'  => BudgetTracking::generateJoinCode(),
            'status'     => 'active',
        ]);
        BudgetTrackingMember::create(['budget_tracking_id' => $bt->id, 'user_id' => $owner->id,  'role' => 'owner',  'joined_at' => now()]);
        BudgetTrackingMember::create(['budget_tracking_id' => $bt->id, 'user_id' => $member->id, 'role' => 'member', 'joined_at' => now()]);

        // Member's dashboard should show the same shared budget
        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/dashboard');
        $bt_monitor = $response->json('data.budget_monitor.budget_tracking');

        $this->assertNotNull($bt_monitor);
        $this->assertEquals('Joint Budget', $bt_monitor['name']);
        $this->assertFalse($bt_monitor['is_owner']);   // member, not owner
        $this->assertEquals(2, $bt_monitor['member_count']);
    }

    // ─── Budget Monitor: Structure ────────────────────────────────────────────────

    public function test_budget_monitor_has_correct_structure(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure(['data' => [
                'budget_monitor' => [
                    'personal' => [
                        'total_budgeted',
                        'total_spent',
                        'total_remaining',
                        'usage_pct',
                        'within_budget',
                        'within_income',
                        'income_surplus',
                        'status',
                        'budget_count',
                        'budgets',
                    ],
                    'budget_tracking',   // nullable
                ],
            ]]);
    }

    public function test_budget_monitor_excludes_budgets_outside_period(): void
    {
        $user = User::factory()->create();

        // Past budget (not in current period)
        Budget::create([
            'user_id'         => $user->id,
            'name'            => 'Old Budget',
            'amount'          => 5000,
            'period'          => 'monthly',
            'start_date'      => '2024-01-01',
            'end_date'        => '2024-01-31',
            'alert_threshold' => 80,
        ]);

        // Current budget
        Budget::create([
            'user_id'         => $user->id,
            'name'            => 'Current Budget',
            'amount'          => 7000,
            'period'          => 'monthly',
            'start_date'      => now()->startOfMonth()->toDateString(),
            'end_date'        => now()->endOfMonth()->toDateString(),
            'alert_threshold' => 80,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $personal = $response->json('data.budget_monitor.personal');

        // Only the current budget should appear
        $this->assertCount(1, $personal['budgets']);
        $this->assertEquals('Current Budget', $personal['budgets'][0]['name']);
    }

    // ─── New Dashboard Sections ───────────────────────────────────────────────────

    public function test_dashboard_balance_subtracts_debt_payments(): void
    {
        $user = User::factory()->create();

        Income::create(['user_id' => $user->id, 'title' => 'Salary', 'amount' => 50000,
            'received_at' => now()->format('Y-m-d'), 'is_recurring' => false]);

        Expense::create(['user_id' => $user->id, 'title' => 'Rent', 'amount' => 15000,
            'spent_at' => now()->format('Y-m-d'), 'is_recurring' => false]);

        $debt = Debt::create([
            'user_id'           => $user->id,
            'lender_name'       => 'Bank',
            'amount'            => 100000,
            'remaining_balance' => 100000,
            'interest_rate'     => 5,
            'due_date'          => now()->addYear()->format('Y-m-d'),
            'status'            => 'active',
            'type'              => 'personal',
        ]);

        Payment::create([
            'user_id'      => $user->id,
            'debt_id'      => $debt->id,
            'amount'       => 5000,
            'payment_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $data = $response->json('data');

        // balance = 50000 - 15000 - 5000 = 30000
        $this->assertEquals(50000, $data['total_income']);
        $this->assertEquals(15000, $data['total_expenses']);
        $this->assertEquals(5000,  $data['total_debt_payments']);
        $this->assertEquals(30000, $data['balance']);
    }

    public function test_dashboard_budget_list_shows_all_user_budgets(): void
    {
        $user = User::factory()->create();

        Budget::create(['user_id' => $user->id, 'name' => 'Food', 'amount' => 5000,
            'period' => 'monthly', 'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(), 'alert_threshold' => 80]);

        Budget::create(['user_id' => $user->id, 'name' => 'Transport', 'amount' => 3000,
            'period' => 'monthly', 'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(), 'alert_threshold' => 80]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();

        $budgetList = $response->json('data.budget_list');
        $this->assertCount(2, $budgetList);

        // Each item has the expected fields
        $this->assertArrayHasKey('id',               $budgetList[0]);
        $this->assertArrayHasKey('name',             $budgetList[0]);
        $this->assertArrayHasKey('allocated_amount', $budgetList[0]);
        $this->assertArrayHasKey('spent_amount',     $budgetList[0]);
        $this->assertArrayHasKey('remaining_amount', $budgetList[0]);
        $this->assertArrayHasKey('usage_pct',        $budgetList[0]);
        $this->assertArrayHasKey('status',           $budgetList[0]);
    }

    public function test_dashboard_budget_list_remaining_decreases_when_expense_added(): void
    {
        $user = User::factory()->create();

        $budget = Budget::create(['user_id' => $user->id, 'name' => 'Food', 'amount' => 10000,
            'period' => 'monthly', 'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(), 'alert_threshold' => 80]);

        // Expense linked to this budget
        Expense::create(['user_id' => $user->id, 'budget_id' => $budget->id,
            'title' => 'Groceries', 'amount' => 4000,
            'spent_at' => now()->format('Y-m-d'), 'is_recurring' => false]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $budgetList = $response->json('data.budget_list');

        $this->assertEquals(10000, $budgetList[0]['allocated_amount']);
        $this->assertEquals(4000,  $budgetList[0]['spent_amount']);
        $this->assertEquals(6000,  $budgetList[0]['remaining_amount']);
        $this->assertEquals(40.0,  $budgetList[0]['usage_pct']);
    }

    public function test_dashboard_debt_list_shows_active_debts(): void
    {
        $user = User::factory()->create();

        Debt::create([
            'user_id'           => $user->id,
            'lender_name'       => 'SSS',
            'amount'            => 50000,
            'remaining_balance' => 45000,
            'interest_rate'     => 3,
            'due_date'          => now()->addMonths(6)->format('Y-m-d'),
            'status'            => 'active',
            'type'              => 'personal',
        ]);

        Debt::create([
            'user_id'           => $user->id,
            'lender_name'       => 'Bank',
            'amount'            => 20000,
            'remaining_balance' => 0,
            'interest_rate'     => 0,
            'due_date'          => now()->subMonth()->format('Y-m-d'),
            'status'            => 'paid',
            'type'              => 'personal',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $debtList = $response->json('data.debt_list');

        // Only active debts appear
        $this->assertCount(1, $debtList);
        $this->assertEquals('SSS',   $debtList[0]['lender_name']);
        $this->assertEquals(50000,   $debtList[0]['original_amount']);
        $this->assertEquals(45000,   $debtList[0]['remaining_balance']);
        $this->assertEquals(5000,    $debtList[0]['total_paid']);
    }

    public function test_dashboard_month_report_structure(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();

        $monthReport = $response->json('data.month_report');

        $this->assertArrayHasKey('period',            $monthReport);
        $this->assertArrayHasKey('total_income',      $monthReport);
        $this->assertArrayHasKey('total_expenses',    $monthReport);
        $this->assertArrayHasKey('debt_payments',     $monthReport);
        $this->assertArrayHasKey('balance',           $monthReport);
        $this->assertArrayHasKey('total_debt',        $monthReport);
        $this->assertArrayHasKey('total_investments', $monthReport);
        $this->assertArrayHasKey('balance_remaining', $monthReport);
        $this->assertArrayHasKey('savings_rate_pct',  $monthReport);
        $this->assertEquals(now()->format('F Y'),     $monthReport['period']);
    }

    public function test_dashboard_year_report_structure(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();

        $yearReport = $response->json('data.year_report');

        $this->assertArrayHasKey('period',            $yearReport);
        $this->assertArrayHasKey('total_income',      $yearReport);
        $this->assertArrayHasKey('total_expenses',    $yearReport);
        $this->assertArrayHasKey('debt_payments',     $yearReport);
        $this->assertArrayHasKey('balance',           $yearReport);
        $this->assertArrayHasKey('total_debt',        $yearReport);
        $this->assertArrayHasKey('total_investments', $yearReport);
        $this->assertArrayHasKey('balance_remaining', $yearReport);
        $this->assertEquals((string) now()->year,     $yearReport['period']);
    }

    public function test_dashboard_month_report_calculates_correctly(): void
    {
        $user = User::factory()->create();

        Income::create(['user_id' => $user->id, 'title' => 'Salary', 'amount' => 60000,
            'received_at' => now()->format('Y-m-d'), 'is_recurring' => false]);

        Expense::create(['user_id' => $user->id, 'title' => 'Rent', 'amount' => 20000,
            'spent_at' => now()->format('Y-m-d'), 'is_recurring' => false]);

        $debt = Debt::create(['user_id' => $user->id, 'lender_name' => 'Bank', 'amount' => 100000,
            'remaining_balance' => 100000, 'interest_rate' => 5,
            'due_date' => now()->addYear()->format('Y-m-d'), 'status' => 'active', 'type' => 'personal']);

        Payment::create(['user_id' => $user->id, 'debt_id' => $debt->id,
            'amount' => 5000, 'payment_date' => now()->format('Y-m-d')]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $month = $response->json('data.month_report');

        $this->assertEquals(60000, $month['total_income']);
        $this->assertEquals(20000, $month['total_expenses']);
        $this->assertEquals(5000,  $month['debt_payments']);
        $this->assertEquals(35000, $month['balance']);          // 60000 - 20000 - 5000
        $this->assertEquals(35000, $month['balance_remaining']);
    }

    public function test_dashboard_year_report_aggregates_full_year(): void
    {
        $user = User::factory()->create();

        // Income in January of the current year
        Income::create(['user_id' => $user->id, 'title' => 'Jan Salary', 'amount' => 40000,
            'received_at' => now()->startOfYear()->format('Y-m-d'), 'is_recurring' => false]);

        // Income this month
        Income::create(['user_id' => $user->id, 'title' => 'This Month', 'amount' => 40000,
            'received_at' => now()->format('Y-m-d'), 'is_recurring' => false]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $year = $response->json('data.year_report');

        // Both incomes fall within the current year
        $this->assertEquals(80000, $year['total_income']);
    }

    public function test_dashboard_transactions_endpoint_returns_paginated_list(): void
    {
        $user = User::factory()->create();

        // Create 12 incomes and 3 expenses → 15 total
        for ($i = 1; $i <= 12; $i++) {
            Income::create(['user_id' => $user->id, 'title' => "Income $i",
                'amount' => 1000 * $i, 'received_at' => now()->subDays($i)->format('Y-m-d'),
                'is_recurring' => false]);
        }
        for ($i = 1; $i <= 3; $i++) {
            Expense::create(['user_id' => $user->id, 'title' => "Expense $i",
                'amount' => 500 * $i, 'spent_at' => now()->subDays($i)->format('Y-m-d'),
                'is_recurring' => false]);
        }

        // First page (default 10)
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/dashboard/transactions');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(10, $data['data']);
        $this->assertEquals(15, $data['total']);
        $this->assertTrue($data['has_more']);
        $this->assertEquals(1, $data['current_page']);

        // Second page
        $page2 = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/dashboard/transactions?page=2');

        $page2->assertOk();
        $this->assertCount(5, $page2->json('data.data'));
        $this->assertFalse($page2->json('data.has_more'));
    }

    public function test_dashboard_recent_transactions_shows_10_with_meta(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 15; $i++) {
            Expense::create(['user_id' => $user->id, 'title' => "Tx $i",
                'amount' => 100, 'spent_at' => now()->subDays($i)->format('Y-m-d'),
                'is_recurring' => false]);
        }

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $tx = $response->json('data.recent_transactions');

        $this->assertCount(10, $tx['data']);
        $this->assertEquals(15, $tx['total']);
        $this->assertTrue($tx['has_more']);
    }
}
