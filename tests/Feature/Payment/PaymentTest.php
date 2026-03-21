<?php

namespace Tests\Feature\Payment;

use App\Models\Debt;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_record_payment(): void
    {
        $user = $this->createUser();
        $this->addIncome($user, 100000);
        $debt = Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'lender_name'        => 'Test Bank',
            'amount'             => 50000,
            'remaining_balance'  => 50000,
            'status'             => 'active',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments', [
            'debt_id'      => $debt->id,
            'amount'       => 5000,
            'payment_date' => '2024-01-15',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_payment_reduces_debt_remaining_balance(): void
    {
        $user = $this->createUser();
        $this->addIncome($user, 100000);
        $debt = Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'lender_name'        => 'Test Bank',
            'amount'             => 50000,
            'remaining_balance'  => 50000,
            'status'             => 'active',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments', [
            'debt_id'      => $debt->id,
            'amount'       => 10000,
            'payment_date' => '2024-01-15',
        ]);

        $debt->refresh();
        $this->assertEquals(40000.0, (float) $debt->remaining_balance);
    }

    public function test_can_list_payments_for_debt(): void
    {
        $user = $this->createUser();
        $debt = Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'lender_name'        => 'Test Bank',
            'amount'             => 50000,
            'remaining_balance'  => 50000,
            'status'             => 'active',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);

        Payment::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'debt_id'            => $debt->id,
            'amount'             => 5000,
            'payment_date'       => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/payments?debt_id={$debt->id}");
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_cannot_access_other_users_payment(): void
    {
        $user      = $this->createUser();
        $otherUser = $this->createUser();
        $debt = Debt::create([
            'user_id'            => $otherUser->id,
            'budget_tracking_id' => $this->getBT($otherUser)->id,
            'lender_name'        => 'Other Bank',
            'amount'             => 50000,
            'remaining_balance'  => 50000,
            'status'             => 'active',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);

        $payment = Payment::create([
            'user_id'            => $otherUser->id,
            'budget_tracking_id' => $this->getBT($otherUser)->id,
            'debt_id'            => $debt->id,
            'amount'             => 5000,
            'payment_date'       => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/payments/{$payment->id}");
        $response->assertStatus(403);
    }

    public function test_payment_validation_requires_debt_id(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments', [
            'amount'       => 5000,
            'payment_date' => '2024-01-15',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_payment_validation_requires_amount(): void
    {
        $user = $this->createUser();
        $debt = Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'lender_name'        => 'Test Bank',
            'amount'             => 50000,
            'remaining_balance'  => 50000,
            'status'             => 'active',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments', [
            'debt_id'      => $debt->id,
            'payment_date' => '2024-01-15',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_full_payment_marks_debt_as_paid(): void
    {
        $user = $this->createUser();
        $this->addIncome($user, 100000);
        $debt = Debt::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'lender_name'        => 'Test Bank',
            'amount'             => 10000,
            'remaining_balance'  => 10000,
            'status'             => 'active',
            'type'               => 'personal',
            'personal_mode'      => 'shop_pay_later',
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments', [
            'debt_id'      => $debt->id,
            'amount'       => 10000,
            'payment_date' => '2024-01-15',
        ]);

        $debt->refresh();
        $this->assertEquals('paid', $debt->status);
    }
}
