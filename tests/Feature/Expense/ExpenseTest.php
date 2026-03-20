<?php

namespace Tests\Feature\Expense;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────────

    /**
     * Create a budget that covers the current month for the given user.
     */
    private function makeMonthBudget(User $user, array $overrides = []): Budget
    {
        return Budget::create(array_merge([
            'user_id'         => $user->id,
            'name'            => 'Test Budget',
            'amount'          => 10000,
            'period'          => 'monthly',
            'start_date'      => now()->startOfMonth()->toDateString(),
            'end_date'        => now()->endOfMonth()->toDateString(),
            'alert_threshold' => 80,
        ], $overrides));
    }

    // ── Basic CRUD ────────────────────────────────────────────────────────────────

    public function test_can_list_expenses(): void
    {
        $user   = User::factory()->create();
        $budget = $this->makeMonthBudget($user);

        Expense::create([
            'user_id'   => $user->id,
            'budget_id' => $budget->id,
            'title'     => 'Groceries',
            'amount'    => 2000,
            'spent_at'  => now()->toDateString(),
        ]);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/expenses')
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_can_create_expense(): void
    {
        $user   = User::factory()->create();
        $budget = $this->makeMonthBudget($user);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'budget_id' => $budget->id,
            'title'     => 'Restaurant',
            'amount'    => 500,
            'spent_at'  => now()->toDateString(),
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Restaurant')
            ->assertJsonPath('data.budget_id', $budget->id);
    }

    public function test_create_expense_fails_without_budget_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'title'    => 'No budget',
            'amount'   => 500,
            'spent_at' => now()->toDateString(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.budget_id.0', 'Please select a budget for this expense.');
    }

    public function test_create_expense_fails_with_other_users_budget(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $otherBudget = $this->makeMonthBudget($other, ['name' => 'Other Budget']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'budget_id' => $otherBudget->id,
            'title'     => 'Attempt',
            'amount'    => 500,
            'spent_at'  => now()->toDateString(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.budget_id.0', 'The selected budget does not exist or does not belong to you.');
    }

    public function test_create_expense_validation_fails_missing_required_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'title' => 'Only title, missing amount and spent_at',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_show_expense(): void
    {
        $user   = User::factory()->create();
        $budget = $this->makeMonthBudget($user);

        $expense = Expense::create([
            'user_id'   => $user->id,
            'budget_id' => $budget->id,
            'title'     => 'Electric Bill',
            'amount'    => 1500,
            'spent_at'  => now()->toDateString(),
        ]);

        $this->actingAs($user, 'sanctum')->getJson("/api/v1/expenses/{$expense->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $expense->id);
    }

    public function test_cannot_show_other_users_expense(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();
        $budget    = $this->makeMonthBudget($otherUser);

        $expense = Expense::create([
            'user_id'   => $otherUser->id,
            'budget_id' => $budget->id,
            'title'     => 'Other Expense',
            'amount'    => 1000,
            'spent_at'  => now()->toDateString(),
        ]);

        $this->actingAs($user, 'sanctum')->getJson("/api/v1/expenses/{$expense->id}")
            ->assertStatus(403);
    }

    public function test_can_update_expense(): void
    {
        $user    = User::factory()->create();
        $budget  = $this->makeMonthBudget($user);
        $expense = Expense::create([
            'user_id'   => $user->id,
            'budget_id' => $budget->id,
            'title'     => 'Old Title',
            'amount'    => 1000,
            'spent_at'  => now()->toDateString(),
        ]);

        $this->actingAs($user, 'sanctum')->putJson("/api/v1/expenses/{$expense->id}", [
            'title'  => 'Updated Expense',
            'amount' => 2000,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Expense');
    }

    public function test_can_change_budget_when_updating_expense(): void
    {
        $user    = User::factory()->create();
        $budgetA = $this->makeMonthBudget($user, ['name' => 'Budget A', 'amount' => 5000]);
        $budgetB = $this->makeMonthBudget($user, ['name' => 'Budget B', 'amount' => 8000]);

        $expense = Expense::create([
            'user_id'   => $user->id,
            'budget_id' => $budgetA->id,
            'title'     => 'Lunch',
            'amount'    => 300,
            'spent_at'  => now()->toDateString(),
        ]);

        $this->actingAs($user, 'sanctum')->putJson("/api/v1/expenses/{$expense->id}", [
            'budget_id' => $budgetB->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.budget_id', $budgetB->id)
            ->assertJsonPath('data.budget_impact.budget_name', 'Budget B');
    }

    public function test_cannot_update_budget_to_other_users_budget(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $myBudget    = $this->makeMonthBudget($user,      ['name' => 'Mine']);
        $otherBudget = $this->makeMonthBudget($otherUser, ['name' => 'Theirs']);

        $expense = Expense::create([
            'user_id'   => $user->id,
            'budget_id' => $myBudget->id,
            'title'     => 'Dinner',
            'amount'    => 500,
            'spent_at'  => now()->toDateString(),
        ]);

        $this->actingAs($user, 'sanctum')->putJson("/api/v1/expenses/{$expense->id}", [
            'budget_id' => $otherBudget->id,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.budget_id.0', 'The selected budget does not exist or does not belong to you.');
    }

    public function test_can_delete_expense(): void
    {
        $user   = User::factory()->create();
        $budget = $this->makeMonthBudget($user);

        $expense = Expense::create([
            'user_id'   => $user->id,
            'budget_id' => $budget->id,
            'title'     => 'To Delete',
            'amount'    => 500,
            'spent_at'  => now()->toDateString(),
        ]);

        $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/expenses/{$expense->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_cannot_update_other_users_expense(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();
        $budget    = $this->makeMonthBudget($otherUser);

        $expense = Expense::create([
            'user_id'   => $otherUser->id,
            'budget_id' => $budget->id,
            'title'     => 'Other Expense',
            'amount'    => 1000,
            'spent_at'  => now()->toDateString(),
        ]);

        $this->actingAs($user, 'sanctum')->putJson("/api/v1/expenses/{$expense->id}", [
            'title' => 'Hijacked',
        ])
            ->assertStatus(403);
    }

    public function test_expense_list_only_shows_own_records(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();
        $myBudget  = $this->makeMonthBudget($user);
        $otherBudget = $this->makeMonthBudget($otherUser);

        Expense::create(['user_id' => $user->id,      'budget_id' => $myBudget->id,    'title' => 'My Expense',    'amount' => 500,  'spent_at' => now()->toDateString()]);
        Expense::create(['user_id' => $otherUser->id, 'budget_id' => $otherBudget->id, 'title' => 'Other Expense', 'amount' => 1000, 'spent_at' => now()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/expenses');
        $response->assertOk();
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('My Expense', $data[0]['title']);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/expenses')->assertStatus(401);
    }

    // ── Budget assignment & impact ─────────────────────────────────────────────────

    public function test_expense_is_linked_to_selected_budget(): void
    {
        $user     = User::factory()->create();
        $category = Category::create(['name' => 'Food', 'user_id' => $user->id, 'type' => 'expense']);

        $foodBudget    = $this->makeMonthBudget($user, ['name' => 'Food Budget',    'category_id' => $category->id, 'amount' => 5000]);
        $generalBudget = $this->makeMonthBudget($user, ['name' => 'General Budget', 'amount' => 20000]);

        // Explicitly select the general budget — category-specific budget is NOT auto-selected
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'budget_id'   => $generalBudget->id,
            'category_id' => $category->id,
            'title'       => 'Supermarket',
            'amount'      => 1000,
            'spent_at'    => now()->toDateString(),
        ]);

        $response->assertStatus(201);
        $this->assertEquals($generalBudget->id, $response->json('data.budget_id'));
        $this->assertEquals('General Budget', $response->json('data.budget_impact.budget_name'));
    }

    public function test_expense_budget_impact_is_returned_on_create(): void
    {
        $user   = User::factory()->create();
        $budget = $this->makeMonthBudget($user, ['amount' => 5000]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'budget_id' => $budget->id,
            'title'     => 'Groceries',
            'amount'    => 1500,
            'spent_at'  => now()->toDateString(),
        ]);

        $response->assertStatus(201);
        $impact = $response->json('data.budget_impact');

        $this->assertNotNull($impact);
        $this->assertEquals('Test Budget',  $impact['budget_name']);
        $this->assertEquals(5000,           $impact['allocated_amount']);
        $this->assertEquals(1500,           $impact['spent_amount']);
        $this->assertEquals(3500,           $impact['remaining_amount']);
        $this->assertEquals(30.0,           $impact['usage_pct']);
        $this->assertEquals('on_track',     $impact['status']);
    }

    public function test_expense_budget_impact_shows_warning_status(): void
    {
        $user   = User::factory()->create();
        $budget = $this->makeMonthBudget($user, ['amount' => 1000]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'budget_id' => $budget->id,
            'title'     => 'Big Expense',
            'amount'    => 850,
            'spent_at'  => now()->toDateString(),
        ]);

        $response->assertStatus(201);
        $this->assertEquals('warning', $response->json('data.budget_impact.status'));
        $this->assertEquals(85.0,      $response->json('data.budget_impact.usage_pct'));
    }

    public function test_expense_budget_impact_shows_over_budget_status_with_negative_remaining(): void
    {
        $user   = User::factory()->create();
        $budget = $this->makeMonthBudget($user, ['amount' => 1000]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'budget_id' => $budget->id,
            'title'     => 'Over the limit',
            'amount'    => 1200,
            'spent_at'  => now()->toDateString(),
        ]);

        $response->assertStatus(201);
        $impact = $response->json('data.budget_impact');

        $this->assertEquals('over_budget', $impact['status']);
        $this->assertEquals(120.0,         $impact['usage_pct']);
        // Remaining is negative — not clipped to zero
        $this->assertEquals(-200.0,        $impact['remaining_amount']);
    }

    public function test_budget_remaining_amount_goes_negative_when_overspent(): void
    {
        $user   = User::factory()->create();
        $budget = $this->makeMonthBudget($user, ['amount' => 3000]);

        // Two expenses totalling 4500 against a 3000 budget
        Expense::create(['user_id' => $user->id, 'budget_id' => $budget->id, 'title' => 'Rent',  'amount' => 3000, 'spent_at' => now()->toDateString()]);
        Expense::create(['user_id' => $user->id, 'budget_id' => $budget->id, 'title' => 'Extra', 'amount' => 1500, 'spent_at' => now()->toDateString()]);

        $budget->refresh();

        $this->assertEquals(4500,   $budget->spent_amount);
        $this->assertEquals(-1500,  $budget->remaining_amount);   // negative, not zero
        $this->assertEquals(150.0,  $budget->usage_percentage);
    }

    public function test_budget_spent_amount_counts_all_linked_expenses(): void
    {
        $user   = User::factory()->create();
        $budget = $this->makeMonthBudget($user, ['amount' => 10000]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'budget_id' => $budget->id, 'title' => 'Expense A', 'amount' => 2000, 'spent_at' => now()->toDateString(),
        ]);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'budget_id' => $budget->id, 'title' => 'Expense B', 'amount' => 3000, 'spent_at' => now()->toDateString(),
        ]);

        $budget->refresh();
        $this->assertEquals(5000.0, $budget->spent_amount);
        $this->assertEquals(5000.0, $budget->remaining_amount);
        $this->assertEquals(50.0,   $budget->usage_percentage);
    }

    public function test_expense_list_is_filterable_by_budget(): void
    {
        $user    = User::factory()->create();
        $budgetA = $this->makeMonthBudget($user, ['name' => 'Budget A']);
        $budgetB = $this->makeMonthBudget($user, ['name' => 'Budget B']);

        Expense::create(['user_id' => $user->id, 'budget_id' => $budgetA->id, 'title' => 'A Expense', 'amount' => 100, 'spent_at' => now()->toDateString()]);
        Expense::create(['user_id' => $user->id, 'budget_id' => $budgetB->id, 'title' => 'B Expense', 'amount' => 200, 'spent_at' => now()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/expenses?budget_id={$budgetA->id}");

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('A Expense', $data[0]['title']);
    }

    public function test_deleted_budget_cannot_be_used_for_new_expense(): void
    {
        $user   = User::factory()->create();
        $budget = $this->makeMonthBudget($user);
        $budget->delete();   // soft-delete the budget

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'budget_id' => $budget->id,
            'title'     => 'Ghost Expense',
            'amount'    => 500,
            'spent_at'  => now()->toDateString(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.budget_id.0', 'The selected budget does not exist or does not belong to you.');
    }
}
