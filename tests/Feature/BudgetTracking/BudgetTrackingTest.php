<?php

namespace Tests\Feature\BudgetTracking;

use App\Models\BudgetTracking;
use App\Models\BudgetTrackingAllocation;
use App\Models\BudgetTrackingMember;
use App\Models\BudgetTrackingTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTrackingTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    private function makeUser(string $name = 'Alice'): User
    {
        return User::factory()->create(['name' => $name]);
    }

    private function actingAsUser(User $user): static
    {
        $token = $user->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', "Bearer $token");
        return $this;
    }

    private function createBudgetTracking(User $user, array $overrides = []): BudgetTracking
    {
        $budget = BudgetTracking::create(array_merge([
            'owner_id'    => $user->id,
            'name'        => 'Family Budget',
            'currency'    => 'PHP',
            'period'      => 'monthly',
            'start_date'  => '2026-01-01',
            'end_date'    => '2026-12-31',
            'join_code'   => BudgetTracking::generateJoinCode(),
            'status'      => 'active',
        ], $overrides));

        BudgetTrackingMember::create([
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
            'role'               => 'owner',
            'joined_at'          => now(),
        ]);

        return $budget;
    }

    private function joinBudget(User $user, BudgetTracking $budget): void
    {
        BudgetTrackingMember::create([
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
            'role'               => 'member',
            'joined_at'          => now(),
        ]);
    }

    // ─── CREATE ───────────────────────────────────────────────────────────────────

    public function test_user_can_create_budget_tracking(): void
    {
        $user = $this->makeUser();
        $this->actingAsUser($user);

        $response = $this->postJson('/api/v1/budget-tracking', [
            'name'       => 'Sarabia Family Budget',
            'period'     => 'monthly',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Sarabia Family Budget')
            ->assertJsonPath('data.is_owner', true);

        $this->assertDatabaseHas('budget_trackings', ['owner_id' => $user->id]);
        $this->assertDatabaseHas('budget_tracking_members', ['user_id' => $user->id, 'role' => 'owner']);
        $this->assertDatabaseHas('budget_tracking_histories', ['action' => 'budget_created']);
    }

    public function test_user_cannot_create_second_budget_tracking(): void
    {
        $user = $this->makeUser();
        $this->createBudgetTracking($user);
        $this->actingAsUser($user);

        $response = $this->postJson('/api/v1/budget-tracking', [
            'name'       => 'Another Budget',
            'period'     => 'monthly',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_generates_unique_join_code(): void
    {
        $user = $this->makeUser();
        $this->actingAsUser($user);

        $this->postJson('/api/v1/budget-tracking', [
            'name'       => 'Test Budget',
            'period'     => 'monthly',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
        ])->assertStatus(201);

        $budget = BudgetTracking::where('owner_id', $user->id)->first();
        $this->assertNotNull($budget->join_code);
        $this->assertEquals(8, strlen($budget->join_code));
    }

    public function test_create_requires_name_and_dates(): void
    {
        $user = $this->makeUser();
        $this->actingAsUser($user);

        $this->postJson('/api/v1/budget-tracking', [])->assertStatus(422);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────────

    public function test_user_can_view_own_budget_tracking(): void
    {
        $user = $this->makeUser();
        $budget = $this->createBudgetTracking($user);
        $this->actingAsUser($user);

        $response = $this->getJson('/api/v1/budget-tracking');

        $response->assertOk()
            ->assertJsonPath('data.id', $budget->id)
            ->assertJsonPath('data.join_code', $budget->join_code)
            ->assertJsonPath('data.is_owner', true);
    }

    public function test_user_without_budget_tracking_gets_404(): void
    {
        $user = $this->makeUser();
        $this->actingAsUser($user);

        $this->getJson('/api/v1/budget-tracking')->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────────

    public function test_owner_can_update_budget_tracking(): void
    {
        $user = $this->makeUser();
        $this->createBudgetTracking($user);
        $this->actingAsUser($user);

        $response = $this->putJson('/api/v1/budget-tracking', [
            'name' => 'Updated Budget Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Budget Name');

        $this->assertDatabaseHas('budget_tracking_histories', ['action' => 'budget_updated']);
    }

    public function test_member_cannot_update_budget_tracking(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);
        $this->actingAsUser($member);

        $this->putJson('/api/v1/budget-tracking', ['name' => 'Hacked Name'])
            ->assertStatus(422);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────────

    public function test_owner_can_delete_budget_tracking(): void
    {
        $user = $this->makeUser();
        $budget = $this->createBudgetTracking($user);
        $this->actingAsUser($user);

        $this->deleteJson('/api/v1/budget-tracking')->assertOk();

        $this->assertSoftDeleted('budget_trackings', ['id' => $budget->id]);
    }

    public function test_member_cannot_delete_budget_tracking(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);
        $this->actingAsUser($member);

        $this->deleteJson('/api/v1/budget-tracking')->assertStatus(422);
    }

    // ─── JOIN BY CODE ─────────────────────────────────────────────────────────────

    public function test_user_can_join_budget_tracking_by_code(): void
    {
        $owner  = $this->makeUser('Owner');
        $joiner = $this->makeUser('Joiner');
        $budget = $this->createBudgetTracking($owner);
        $this->actingAsUser($joiner);

        $response = $this->postJson('/api/v1/budget-tracking/join', [
            'join_code' => $budget->join_code,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $budget->id);

        $this->assertDatabaseHas('budget_tracking_members', [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $joiner->id,
            'role'               => 'member',
        ]);
        $this->assertDatabaseHas('budget_tracking_histories', ['action' => 'member_joined']);
    }

    public function test_user_cannot_join_if_already_has_a_budget_tracking(): void
    {
        $owner1 = $this->makeUser('Owner1');
        $owner2 = $this->makeUser('Owner2');
        $budget1 = $this->createBudgetTracking($owner1);
        $this->createBudgetTracking($owner2);
        $this->actingAsUser($owner2);

        $this->postJson('/api/v1/budget-tracking/join', [
            'join_code' => $budget1->join_code,
        ])->assertStatus(422);
    }

    public function test_cannot_join_with_invalid_code(): void
    {
        $user = $this->makeUser();
        $this->actingAsUser($user);

        $this->postJson('/api/v1/budget-tracking/join', [
            'join_code' => 'BADCODE1',
        ])->assertStatus(404);
    }

    public function test_join_code_must_be_8_characters(): void
    {
        $user = $this->makeUser();
        $this->actingAsUser($user);

        $this->postJson('/api/v1/budget-tracking/join', [
            'join_code' => 'SHORT',
        ])->assertStatus(422);
    }

    // ─── LEAVE ────────────────────────────────────────────────────────────────────

    public function test_member_can_leave_budget_tracking(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);
        $this->actingAsUser($member);

        $this->postJson('/api/v1/budget-tracking/leave')->assertOk();

        $this->assertDatabaseMissing('budget_tracking_members', [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $member->id,
        ]);
    }

    public function test_owner_cannot_leave_budget_tracking(): void
    {
        $user = $this->makeUser();
        $this->createBudgetTracking($user);
        $this->actingAsUser($user);

        $this->postJson('/api/v1/budget-tracking/leave')->assertStatus(422);
    }

    // ─── ONE-PER-USER CONSTRAINT ─────────────────────────────────────────────────

    public function test_shared_budget_counts_as_users_one_budget_tracking(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);
        $this->actingAsUser($member);

        // Member should see the shared budget
        $this->getJson('/api/v1/budget-tracking')
            ->assertOk()
            ->assertJsonPath('data.id', $budget->id);

        // Member cannot create their own
        $this->postJson('/api/v1/budget-tracking', [
            'name'       => 'My Own Budget',
            'period'     => 'monthly',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
        ])->assertStatus(422);
    }

    // ─── REMOVE MEMBER ───────────────────────────────────────────────────────────

    public function test_owner_can_remove_member(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);
        $this->actingAsUser($owner);

        $this->deleteJson("/api/v1/budget-tracking/members/{$member->id}")->assertOk();

        $this->assertDatabaseMissing('budget_tracking_members', [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $member->id,
        ]);
        $this->assertDatabaseHas('budget_tracking_histories', ['action' => 'member_removed']);
    }

    public function test_member_cannot_remove_other_members(): void
    {
        $owner   = $this->makeUser('Owner');
        $member1 = $this->makeUser('Member1');
        $member2 = $this->makeUser('Member2');
        $budget  = $this->createBudgetTracking($owner);
        $this->joinBudget($member1, $budget);
        $this->joinBudget($member2, $budget);
        $this->actingAsUser($member1);

        $this->deleteJson("/api/v1/budget-tracking/members/{$member2->id}")
            ->assertStatus(422);
    }

    // ─── CODE REGENERATION ────────────────────────────────────────────────────────

    public function test_owner_can_regenerate_join_code(): void
    {
        $user = $this->makeUser();
        $budget = $this->createBudgetTracking($user);
        $this->actingAsUser($user);
        $oldCode = $budget->join_code;

        $response = $this->postJson('/api/v1/budget-tracking/code/regenerate');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['join_code']]);

        $newCode = $response->json('data.join_code');
        $this->assertNotEquals($oldCode, $newCode);
        $this->assertEquals(8, strlen($newCode));

        $this->assertDatabaseHas('budget_tracking_histories', ['action' => 'code_regenerated']);
    }

    public function test_member_cannot_regenerate_join_code(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);
        $this->actingAsUser($member);

        $this->postJson('/api/v1/budget-tracking/code/regenerate')->assertStatus(422);
    }

    // ─── ALLOCATIONS ─────────────────────────────────────────────────────────────

    public function test_owner_can_add_allocation(): void
    {
        $user = $this->makeUser();
        $this->createBudgetTracking($user);
        $this->actingAsUser($user);

        $response = $this->postJson('/api/v1/budget-tracking/allocations', [
            'name'             => 'Groceries',
            'allocated_amount' => 5000,
            'color'            => '#4CAF50',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Groceries');

        $this->assertEquals(5000.0, $response->json('data.allocated_amount'));
        $this->assertEquals(0.0,    $response->json('data.spent_amount'));
        $this->assertEquals(5000.0, $response->json('data.remaining_amount'));

        $this->assertDatabaseHas('budget_tracking_histories', ['action' => 'allocation_added']);
    }

    public function test_member_cannot_add_allocation(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);
        $this->actingAsUser($member);

        $this->postJson('/api/v1/budget-tracking/allocations', [
            'name'             => 'Rent',
            'allocated_amount' => 3000,
        ])->assertStatus(422);
    }

    public function test_owner_can_update_and_delete_allocation(): void
    {
        $user   = $this->makeUser();
        $budget = $this->createBudgetTracking($user);
        $this->actingAsUser($user);

        $allocation = BudgetTrackingAllocation::create([
            'budget_tracking_id' => $budget->id,
            'name'               => 'Utilities',
            'allocated_amount'   => 2000,
            'color'              => '#blue',
        ]);

        $this->putJson("/api/v1/budget-tracking/allocations/{$allocation->id}", [
            'name'             => 'Utilities & Bills',
            'allocated_amount' => 2500,
        ])->assertOk()->assertJsonPath('data.name', 'Utilities & Bills');

        $this->deleteJson("/api/v1/budget-tracking/allocations/{$allocation->id}")->assertOk();

        $this->assertSoftDeleted('budget_tracking_allocations', ['id' => $allocation->id]);
    }

    public function test_all_members_can_view_allocations(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);

        BudgetTrackingAllocation::create([
            'budget_tracking_id' => $budget->id,
            'name'               => 'Food',
            'allocated_amount'   => 3000,
            'color'              => '#red',
        ]);

        $this->actingAsUser($member);
        $this->getJson('/api/v1/budget-tracking/allocations')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ─── TRANSACTIONS ─────────────────────────────────────────────────────────────

    public function test_any_member_can_add_transaction(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);
        $this->actingAsUser($member);

        $response = $this->postJson('/api/v1/budget-tracking/transactions', [
            'type'   => 'expense',
            'title'  => 'Groceries run',
            'amount' => 1500,
            'date'   => '2026-03-20',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Groceries run')
            ->assertJsonPath('data.added_by.name', 'Member');

        $this->assertDatabaseHas('budget_tracking_histories', ['action' => 'transaction_added']);
    }

    public function test_member_can_update_own_transaction_only(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);

        // Member adds a transaction
        $tx = BudgetTrackingTransaction::create([
            'budget_tracking_id' => $budget->id,
            'user_id'            => $member->id,
            'type'               => 'expense',
            'title'              => 'Coffee',
            'amount'             => 150,
            'date'               => '2026-03-20',
        ]);

        // Member can update their own
        $this->actingAsUser($member);
        $this->putJson("/api/v1/budget-tracking/transactions/{$tx->id}", [
            'type'   => 'expense',
            'title'  => 'Coffee & Cake',
            'amount' => 200,
            'date'   => '2026-03-20',
        ])->assertOk()->assertJsonPath('data.title', 'Coffee & Cake');
    }

    public function test_member_cannot_update_other_members_transaction(): void
    {
        $owner   = $this->makeUser('Owner');
        $member1 = $this->makeUser('Member1');
        $member2 = $this->makeUser('Member2');
        $budget  = $this->createBudgetTracking($owner);
        $this->joinBudget($member1, $budget);
        $this->joinBudget($member2, $budget);

        $tx = BudgetTrackingTransaction::create([
            'budget_tracking_id' => $budget->id,
            'user_id'            => $member1->id,
            'type'               => 'expense',
            'title'              => 'Member1 purchase',
            'amount'             => 500,
            'date'               => '2026-03-20',
        ]);

        $this->actingAsUser($member2);
        $this->putJson("/api/v1/budget-tracking/transactions/{$tx->id}", [
            'type'   => 'expense',
            'title'  => 'Hacked',
            'amount' => 1,
            'date'   => '2026-03-20',
        ])->assertStatus(422);
    }

    public function test_owner_can_delete_any_transaction(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);

        $tx = BudgetTrackingTransaction::create([
            'budget_tracking_id' => $budget->id,
            'user_id'            => $member->id,
            'type'               => 'expense',
            'title'              => 'Member expense',
            'amount'             => 300,
            'date'               => '2026-03-20',
        ]);

        $this->actingAsUser($owner);
        $this->deleteJson("/api/v1/budget-tracking/transactions/{$tx->id}")->assertOk();

        $this->assertSoftDeleted('budget_tracking_transactions', ['id' => $tx->id]);
    }

    // ─── SUMMARY ─────────────────────────────────────────────────────────────────

    public function test_summary_shows_totals_and_per_member_breakdown(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);
        $this->joinBudget($member, $budget);

        $allocation = BudgetTrackingAllocation::create([
            'budget_tracking_id' => $budget->id,
            'name'               => 'Rent',
            'allocated_amount'   => 5000,
            'color'              => '#blue',
        ]);

        BudgetTrackingTransaction::create([
            'budget_tracking_id'             => $budget->id,
            'user_id'                        => $owner->id,
            'budget_tracking_allocation_id'  => $allocation->id,
            'type'                           => 'income',
            'title'                          => 'Salary',
            'amount'                         => 50000,
            'date'                           => '2026-03-01',
        ]);

        BudgetTrackingTransaction::create([
            'budget_tracking_id'             => $budget->id,
            'user_id'                        => $member->id,
            'budget_tracking_allocation_id'  => $allocation->id,
            'type'                           => 'expense',
            'title'                          => 'Rent payment',
            'amount'                         => 3000,
            'date'                           => '2026-03-05',
        ]);

        $this->actingAsUser($owner);
        $response = $this->getJson('/api/v1/budget-tracking/summary');

        $response->assertOk()
            ->assertJsonPath('data.member_count', 2);

        $this->assertEquals(50000.0, $response->json('data.total_income'));
        $this->assertEquals(3000.0,  $response->json('data.total_expense'));
        $this->assertEquals(47000.0, $response->json('data.balance'));
        $this->assertEquals(5000.0,  $response->json('data.total_allocated'));

        $this->assertEquals(2, count($response->json('data.by_member')));
    }

    // ─── HISTORY LOG ─────────────────────────────────────────────────────────────

    public function test_history_is_recorded_for_all_actions(): void
    {
        $owner  = $this->makeUser('Owner');
        $member = $this->makeUser('Member');
        $budget = $this->createBudgetTracking($owner);

        // Log history via service
        $this->actingAsUser($owner);

        // Join
        $this->postJson('/api/v1/budget-tracking/join', ['join_code' => $budget->join_code])
            ->assertStatus(422); // owner already member, but that's fine

        // Let member join fresh
        $this->actingAsUser($member);
        $this->postJson('/api/v1/budget-tracking/join', ['join_code' => $budget->join_code]);

        // Member adds transaction — creates history
        $this->postJson('/api/v1/budget-tracking/transactions', [
            'type'   => 'expense',
            'title'  => 'Electricity bill',
            'amount' => 800,
            'date'   => '2026-03-10',
        ]);

        $this->actingAsUser($owner);
        $response = $this->getJson('/api/v1/budget-tracking/history');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['data' => [['id', 'action', 'description', 'changed_by', 'created_at']]]]);
    }

    // ─── AUTH ────────────────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_budget_tracking(): void
    {
        $this->getJson('/api/v1/budget-tracking')->assertStatus(401);
        $this->postJson('/api/v1/budget-tracking', [])->assertStatus(401);
        $this->postJson('/api/v1/budget-tracking/join', [])->assertStatus(401);
    }
}
