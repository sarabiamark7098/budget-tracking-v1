<?php

namespace Tests\Feature\Expense;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_expenses(): void
    {
        $user = User::factory()->create();
        Expense::create([
            'user_id' => $user->id,
            'title' => 'Groceries',
            'amount' => 2000,
            'spent_at' => '2024-01-10',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/expenses');
        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_can_create_expense(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'title' => 'Restaurant',
            'amount' => 500,
            'spent_at' => '2024-01-10',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Restaurant');
    }

    public function test_create_expense_validation_fails(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'title' => 'Only title, missing required fields',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_can_show_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::create([
            'user_id' => $user->id,
            'title' => 'Electric Bill',
            'amount' => 1500,
            'spent_at' => '2024-01-10',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/expenses/{$expense->id}");
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $expense->id);
    }

    public function test_cannot_show_other_users_expense(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $expense = Expense::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Expense',
            'amount' => 1000,
            'spent_at' => '2024-01-10',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/expenses/{$expense->id}");
        $response->assertStatus(403);
    }

    public function test_can_update_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::create([
            'user_id' => $user->id,
            'title' => 'Old Title',
            'amount' => 1000,
            'spent_at' => '2024-01-10',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/expenses/{$expense->id}", [
            'title' => 'Updated Expense',
            'amount' => 2000,
            'spent_at' => '2024-01-10',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Expense');
    }

    public function test_can_delete_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::create([
            'user_id' => $user->id,
            'title' => 'To Delete',
            'amount' => 500,
            'spent_at' => '2024-01-10',
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/expenses/{$expense->id}");
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_cannot_update_other_users_expense(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $expense = Expense::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Expense',
            'amount' => 1000,
            'spent_at' => '2024-01-10',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/expenses/{$expense->id}", [
            'title' => 'Hijacked',
            'amount' => 1,
            'spent_at' => '2024-01-10',
        ]);

        $response->assertStatus(403);
    }

    public function test_expense_list_only_shows_own_records(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Expense::create([
            'user_id' => $user->id,
            'title' => 'My Expense',
            'amount' => 500,
            'spent_at' => '2024-01-10',
        ]);
        Expense::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Expense',
            'amount' => 1000,
            'spent_at' => '2024-01-10',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/expenses');
        $response->assertOk();
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('My Expense', $data[0]['title']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/expenses');
        $response->assertStatus(401);
    }
}
