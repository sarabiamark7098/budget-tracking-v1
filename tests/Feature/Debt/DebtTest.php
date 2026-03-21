<?php

namespace Tests\Feature\Debt;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_personal_debt(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/debts', [
            'lender_name'   => 'John Doe',
            'amount'        => 50000,
            'type'          => 'personal',
            'personal_mode' => 'shop_pay_later',
            'due_date'      => '2025-12-31',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'personal');
    }

    public function test_can_create_business_debt(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/debts', [
            'lender_name'   => 'Bank Corp',
            'amount'        => 100000,
            'type'          => 'business',
            'borrower_name' => 'My Business Inc.',
            'interest_rate' => 12.5,
            'due_date'      => '2025-12-31',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'business');
    }

    public function test_business_debt_requires_business_name(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/debts', [
            'lender_name' => 'Bank Corp',
            'amount'      => 100000,
            'type'        => 'business',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_can_update_debt(): void
    {
        $user = $this->createUser();
        $debt = Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'lender_name'        => 'Old Lender',
            'amount'             => 50000,
            'remaining_balance'  => 50000,
            'status'             => 'active',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/debts/{$debt->id}", [
            'lender_name' => 'Updated Lender',
            'amount'      => 50000,
            'type'        => 'personal',
        ]);

        $response->assertOk()->assertJsonPath('data.lender_name', 'Updated Lender');
    }

    public function test_can_delete_debt(): void
    {
        $user = $this->createUser();
        $debt = Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'lender_name'        => 'To Delete',
            'amount'             => 10000,
            'remaining_balance'  => 10000,
            'status'             => 'active',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/debts/{$debt->id}");
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_debt_status_filter_works(): void
    {
        $user = $this->createUser();
        $btId = $this->getBT($user)->id;

        Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'lender_name'        => 'Active Debt',
            'amount'             => 10000,
            'remaining_balance'  => 10000,
            'status'             => 'active',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);
        Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $btId,
            'lender_name'        => 'Paid Debt',
            'amount'             => 5000,
            'remaining_balance'  => 0,
            'status'             => 'paid',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/debts?status=active');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_cannot_access_other_users_debt(): void
    {
        $user      = $this->createUser();
        $otherUser = $this->createUser();
        $debt = Debt::create([
            'user_id'            => $otherUser->id,
            'budget_tracking_id' => $this->getBT($otherUser)->id,
            'lender_name'        => 'Other Lender',
            'amount'             => 10000,
            'remaining_balance'  => 10000,
            'status'             => 'active',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/debts/{$debt->id}");
        $response->assertStatus(403);
    }

    public function test_debt_validation_requires_lender_name(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/debts', [
            'amount' => 50000,
            'type'   => 'personal',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_can_list_debts(): void
    {
        $user = $this->createUser();
        Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'lender_name'        => 'Test Lender',
            'amount'             => 10000,
            'remaining_balance'  => 10000,
            'status'             => 'active',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/debts');
        $response->assertOk()->assertJsonPath('success', true);
    }
}
