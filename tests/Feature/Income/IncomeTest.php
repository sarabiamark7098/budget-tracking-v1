<?php

namespace Tests\Feature\Income;

use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_incomes(): void
    {
        $user = $this->createUser();
        Income::create([
            'user_id' => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'title' => 'Salary',
            'amount' => 50000,
            'received_at' => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/incomes');
        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_can_create_income(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/incomes', [
            'title' => 'Monthly Salary',
            'amount' => 50000,
            'received_at' => '2024-01-15',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Monthly Salary')
            ->assertJsonPath('data.amount', '50000.00');
    }

    public function test_create_income_validation_fails(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/incomes', [
            'title' => 'Missing required fields',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_create_income_validation_requires_title(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/incomes', [
            'amount' => 50000,
            'received_at' => '2024-01-15',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_can_show_income(): void
    {
        $user = $this->createUser();
        $income = Income::create([
            'user_id' => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'title' => 'Salary',
            'amount' => 50000,
            'received_at' => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/incomes/{$income->id}");
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $income->id);
    }

    public function test_cannot_show_other_users_income(): void
    {
        $user = $this->createUser();
        $otherUser = User::factory()->create();
        $income = Income::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Salary',
            'amount' => 50000,
            'received_at' => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/incomes/{$income->id}");
        $response->assertStatus(403);
    }

    public function test_can_update_income(): void
    {
        $user = $this->createUser();
        $income = Income::create([
            'user_id' => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'title' => 'Old Title',
            'amount' => 50000,
            'received_at' => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/incomes/{$income->id}", [
            'title' => 'Updated Salary',
            'amount' => 60000,
            'received_at' => '2024-01-15',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Salary');
    }

    public function test_cannot_update_other_users_income(): void
    {
        $user = $this->createUser();
        $otherUser = User::factory()->create();
        $income = Income::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Salary',
            'amount' => 50000,
            'received_at' => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/incomes/{$income->id}", [
            'title' => 'Hijacked Title',
            'amount' => 1,
            'received_at' => '2024-01-15',
        ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_income(): void
    {
        $user = $this->createUser();
        $income = Income::create([
            'user_id' => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'title' => 'To Delete',
            'amount' => 50000,
            'received_at' => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/incomes/{$income->id}");
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted('incomes', ['id' => $income->id]);
    }

    public function test_cannot_delete_other_users_income(): void
    {
        $user = $this->createUser();
        $otherUser = User::factory()->create();
        $income = Income::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Salary',
            'amount' => 50000,
            'received_at' => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/incomes/{$income->id}");
        $response->assertStatus(403);
    }

    public function test_income_list_filters_by_date(): void
    {
        $user = $this->createUser();
        Income::create([
            'user_id' => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'title' => 'January Income',
            'amount' => 50000,
            'received_at' => '2024-01-15',
        ]);
        Income::create([
            'user_id' => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'title' => 'June Income',
            'amount' => 60000,
            'received_at' => '2024-06-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/incomes?date_from=2024-01-01&date_to=2024-03-31');

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_income_list_only_shows_own_records(): void
    {
        $user = $this->createUser();
        $otherUser = User::factory()->create();

        Income::create([
            'user_id' => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'title' => 'My Income',
            'amount' => 50000,
            'received_at' => '2024-01-15',
        ]);
        Income::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Income',
            'amount' => 80000,
            'received_at' => '2024-01-20',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/incomes');
        $response->assertOk();
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('My Income', $data[0]['title']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/incomes');
        $response->assertStatus(401);
    }
}
