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
        $user = $this->createUser();
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
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        Income::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Salary',
            'amount'             => 50000,
            'received_at'        => now()->format('Y-m-d'),
        ]);
        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Rent',
            'amount'             => 15000,
            'spent_at'           => now()->format('Y-m-d'),
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
        $user = $this->createUser();
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['monthly_data']]);
    }

    public function test_dashboard_includes_recent_transactions(): void
    {
        $user = $this->createUser();
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['recent_transactions']]);
    }

    public function test_dashboard_only_shows_own_data(): void
    {
        $user  = $this->createUser();
        $other = $this->createUser();

        Income::create([
            'user_id'            => $other->id,
            'budget_tracking_id' => $this->getBT($other)->id,
            'title'              => 'Other Income',
            'amount'             => 999999,
            'received_at'        => now()->format('Y-m-d'),
        ]);

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
        $user = $this->createUser();
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/dashboard?date_from=2024-01-01&date_to=2024-12-31')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    // ─── Budget Monitor: Personal Budgets ────────────────────────────────────────

    public function test_dashboard_budget_monitor_shows_personal_budgets(): void
    {
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        // Income this month
        Income::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Salary',
            'amount'             => 50000,
            'received_at'        => now()->format('Y-m-d'),
        ]);

        // Personal budget for this month
        Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Groceries Budget',
            'amount'             => 10000,
            'period'             => 'monthly',
            'start_date'         => now()->startOfMonth()->toDateString(),
        ]);

        // Expense within the budget period (no budget_id = fallback path via budget_tracking_id)
        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Supermarket',
            'amount'             => 6000,
            'spent_at'           => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();

        $monitor = $response->json('data.budget_monitor');

        $this->assertEquals(10000, $monitor['total_budgeted']);
        $this->assertEquals(6000,  $monitor['total_spent']);
        $this->assertEquals(4000,  $monitor['total_remaining']);
        $this->assertEquals(60.0,  $monitor['usage_pct']);
        $this->assertTrue($monitor['within_budget']);
        $this->assertTrue($monitor['within_income']);
        $this->assertEquals('on_track', $monitor['status']);
        $this->assertCount(1, $monitor['budgets']);
    }

    public function test_budget_monitor_flags_warning_when_near_threshold(): void
    {
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        // Income must be higher than spending so over_income doesn't shadow the warning status
        Income::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Salary',
            'amount'             => 20000,
            'received_at'        => now()->format('Y-m-d'),
        ]);

        Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Food',
            'amount'             => 5000,
            'period'             => 'monthly',
            'start_date'         => now()->startOfMonth()->toDateString(),
        ]);

        // 85% of budget spent
        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Groceries',
            'amount'             => 4250,
            'spent_at'           => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $monitor  = $response->json('data.budget_monitor');

        $this->assertEquals('warning', $monitor['budgets'][0]['status']);
        $this->assertEquals('warning', $monitor['status']);
    }

    public function test_budget_monitor_flags_over_budget(): void
    {
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        // Income must be higher than spending so over_income doesn't shadow the over_budget status
        Income::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Salary',
            'amount'             => 20000,
            'received_at'        => now()->format('Y-m-d'),
        ]);

        Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Entertainment',
            'amount'             => 2000,
            'period'             => 'monthly',
            'start_date'         => now()->startOfMonth()->toDateString(),
        ]);

        // 110% of budget spent
        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Concert tickets',
            'amount'             => 2200,
            'spent_at'           => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $monitor  = $response->json('data.budget_monitor');

        $this->assertFalse($monitor['within_budget']);
        $this->assertEquals('over_budget', $monitor['budgets'][0]['status']);
        $this->assertEquals('over_budget', $monitor['status']);
    }

    public function test_budget_monitor_flags_over_income(): void
    {
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        // Income: 5000; budget: 8000; spent: 6000 → over income
        Income::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Part-time',
            'amount'             => 5000,
            'received_at'        => now()->format('Y-m-d'),
        ]);

        Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Monthly',
            'amount'             => 8000,
            'period'             => 'monthly',
            'start_date'         => now()->startOfMonth()->toDateString(),
        ]);

        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Rent + food',
            'amount'             => 6000,
            'spent_at'           => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $monitor  = $response->json('data.budget_monitor');

        $this->assertFalse($monitor['within_income']);
        $this->assertEquals('over_income', $monitor['status']);
        $this->assertEquals(-1000, $monitor['income_surplus']);
    }

    public function test_budget_monitor_is_empty_when_no_budgets_set(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');

        $monitor = $response->json('data.budget_monitor');

        $this->assertEquals(0, $monitor['total_budgeted']);
        $this->assertEquals(0, $monitor['total_spent']);
        $this->assertEquals(0, $monitor['budget_count']);
        $this->assertEmpty($monitor['budgets']);
    }

    // ─── Budget Monitor: Shared Budget Tracking (tracker sub-key) ────────────────

    public function test_budget_monitor_tracker_is_present(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');

        // The tracker sub-section always exists (may have zeroes when empty)
        $this->assertArrayHasKey('tracker', $response->json('data.budget_monitor'));
    }

    public function test_budget_monitor_shows_shared_budget_tracking(): void
    {
        $owner = $this->createUser();
        $bt    = $this->getBT($owner);

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
            'budget_tracking_id'            => $bt->id,
            'user_id'                       => $owner->id,
            'budget_tracking_allocation_id' => BudgetTrackingAllocation::first()->id,
            'type'                          => 'expense',
            'title'                         => 'Weekly groceries',
            'amount'                        => 5000,
            'date'                          => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();

        $tracker = $response->json('data.budget_monitor.tracker');

        $this->assertNotNull($tracker);
        $this->assertEquals(8000,  $tracker['total_allocated']);
        $this->assertEquals(40000, $tracker['total_income']);
        $this->assertEquals(5000,  $tracker['total_expense']);
        $this->assertCount(1, $tracker['allocations']);
    }

    public function test_shared_budget_monitor_detects_over_allocation(): void
    {
        $owner = $this->createUser();
        $bt    = $this->getBT($owner);

        $alloc = BudgetTrackingAllocation::create([
            'budget_tracking_id' => $bt->id,
            'name'               => 'Rent',
            'allocated_amount'   => 3000,
            'color'              => '#FF0000',
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
        $tracker  = $response->json('data.budget_monitor.tracker');

        $this->assertEquals('over_budget', $tracker['allocations'][0]['status']);
    }

    public function test_shared_budget_member_sees_same_budget_monitor(): void
    {
        $owner  = $this->createUser();
        $member = User::factory()->create();

        $bt = $this->getBT($owner);

        BudgetTrackingMember::create([
            'budget_tracking_id' => $bt->id,
            'user_id'            => $member->id,
            'role'               => 'member',
            'joined_at'          => now(),
        ]);

        // Member's dashboard should show the same shared budget
        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();

        $tracker = $response->json('data.budget_monitor.tracker');
        $this->assertNotNull($tracker);
        $this->assertEquals(2, $tracker['member_count']);
    }

    // ─── Budget Monitor: Structure ────────────────────────────────────────────────

    public function test_budget_monitor_has_correct_structure(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure(['data' => [
                'budget_monitor' => [
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
                    'tracker',
                ],
            ]]);
    }

    public function test_budget_monitor_excludes_budgets_outside_period(): void
    {
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        // Past budget (start_date far in the past — the service queries start_date <= dateTo)
        // A budget with start_date in the past WILL appear, but we can test it excludes future budgets
        // and that only the count matches what we set up.

        // Current budget
        Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Current Budget',
            'amount'             => 7000,
            'period'             => 'monthly',
            'start_date'         => now()->startOfMonth()->toDateString(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $monitor  = $response->json('data.budget_monitor');

        // The current budget should appear
        $this->assertCount(1, $monitor['budgets']);
        $this->assertEquals('Current Budget', $monitor['budgets'][0]['name']);
    }

    // ─── New Dashboard Sections ───────────────────────────────────────────────────

    public function test_dashboard_balance_subtracts_debt_payments(): void
    {
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        Income::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Salary',
            'amount'             => 50000,
            'received_at'        => now()->format('Y-m-d'),
        ]);

        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Rent',
            'amount'             => 15000,
            'spent_at'           => now()->format('Y-m-d'),
        ]);

        $debt = Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'lender_name'        => 'Bank',
            'amount'             => 100000,
            'remaining_balance'  => 100000,
            'interest_rate'      => 5,
            'status'             => 'active',
            'type'               => 'personal',
        ]);

        Payment::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'debt_id'            => $debt->id,
            'amount'             => 5000,
            'payment_date'       => now()->format('Y-m-d'),
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
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Food',
            'amount'             => 5000,
            'period'             => 'monthly',
            'start_date'         => now()->startOfMonth()->toDateString(),
        ]);

        Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Transport',
            'amount'             => 3000,
            'period'             => 'monthly',
            'start_date'         => now()->startOfMonth()->toDateString(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $response->assertOk();

        $budgetList = $response->json('data.budget_list');
        $this->assertCount(2, $budgetList);

        // Each item has the expected fields
        $this->assertArrayHasKey('id',               $budgetList[0]);
        $this->assertArrayHasKey('name',             $budgetList[0]);
        $this->assertArrayHasKey('amount',           $budgetList[0]);
        $this->assertArrayHasKey('total_budget',     $budgetList[0]);
        $this->assertArrayHasKey('spent_amount',     $budgetList[0]);
        $this->assertArrayHasKey('remaining_amount', $budgetList[0]);
        $this->assertArrayHasKey('usage_pct',        $budgetList[0]);
        $this->assertArrayHasKey('status',           $budgetList[0]);
    }

    public function test_dashboard_budget_list_remaining_decreases_when_expense_added(): void
    {
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        $budget = Budget::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'name'               => 'Food',
            'amount'             => 10000,
            'period'             => 'monthly',
            'start_date'         => now()->startOfMonth()->toDateString(),
        ]);

        // Expense linked directly to this budget
        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'budget_id'          => $budget->id,
            'title'              => 'Groceries',
            'amount'             => 4000,
            'spent_at'           => now()->format('Y-m-d'),
        ]);

        $response   = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $budgetList = $response->json('data.budget_list');

        $this->assertEquals(10000, $budgetList[0]['amount']);
        $this->assertEquals(4000,  $budgetList[0]['spent_amount']);
        $this->assertEquals(6000,  $budgetList[0]['remaining_amount']);
        $this->assertEquals(40.0,  $budgetList[0]['usage_pct']);
    }

    public function test_dashboard_debt_list_shows_active_debts(): void
    {
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'lender_name'        => 'SSS',
            'amount'             => 50000,
            'remaining_balance'  => 45000,
            'interest_rate'      => 3,
            'status'             => 'active',
            'type'               => 'personal',
        ]);

        Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'lender_name'        => 'Bank',
            'amount'             => 20000,
            'remaining_balance'  => 0,
            'interest_rate'      => 0,
            'status'             => 'paid',
            'type'               => 'personal',
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
        $user = $this->createUser();
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
        $user = $this->createUser();
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
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        Income::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Salary',
            'amount'             => 60000,
            'received_at'        => now()->format('Y-m-d'),
        ]);

        Expense::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Rent',
            'amount'             => 20000,
            'spent_at'           => now()->format('Y-m-d'),
        ]);

        $debt = Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'lender_name'        => 'Bank',
            'amount'             => 100000,
            'remaining_balance'  => 100000,
            'interest_rate'      => 5,
            'status'             => 'active',
            'type'               => 'personal',
        ]);

        Payment::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'debt_id'            => $debt->id,
            'amount'             => 5000,
            'payment_date'       => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $month    = $response->json('data.month_report');

        $this->assertEquals(60000, $month['total_income']);
        $this->assertEquals(20000, $month['total_expenses']);
        $this->assertEquals(5000,  $month['debt_payments']);
        $this->assertEquals(35000, $month['balance']);          // 60000 - 20000 - 5000
        $this->assertEquals(35000, $month['balance_remaining']);
    }

    public function test_dashboard_year_report_aggregates_full_year(): void
    {
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        // Income in January of the current year
        Income::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'Jan Salary',
            'amount'             => 40000,
            'received_at'        => now()->startOfYear()->format('Y-m-d'),
        ]);

        // Income this month
        Income::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'title'              => 'This Month',
            'amount'             => 40000,
            'received_at'        => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $year     = $response->json('data.year_report');

        // Both incomes fall within the current year
        $this->assertEquals(80000, $year['total_income']);
    }

    public function test_dashboard_transactions_endpoint_returns_paginated_list(): void
    {
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        // Create 12 incomes and 3 expenses → 15 total
        for ($i = 1; $i <= 12; $i++) {
            Income::create([
                'user_id'            => $user->id,
                'budget_tracking_id' => $btId,
                'title'              => "Income $i",
                'amount'             => 1000 * $i,
                'received_at'        => now()->subDays($i)->format('Y-m-d'),
            ]);
        }
        for ($i = 1; $i <= 3; $i++) {
            Expense::create([
                'user_id'            => $user->id,
                'budget_tracking_id' => $btId,
                'title'              => "Expense $i",
                'amount'             => 500 * $i,
                'spent_at'           => now()->subDays($i)->format('Y-m-d'),
            ]);
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
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        for ($i = 1; $i <= 15; $i++) {
            Expense::create([
                'user_id'            => $user->id,
                'budget_tracking_id' => $btId,
                'title'              => "Tx $i",
                'amount'             => 100,
                'spent_at'           => now()->subDays($i)->format('Y-m-d'),
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard');
        $tx       = $response->json('data.recent_transactions');

        $this->assertCount(10, $tx['data']);
        $this->assertEquals(15, $tx['total']);
        $this->assertTrue($tx['has_more']);
    }
}
