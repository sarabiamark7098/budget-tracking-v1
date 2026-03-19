<?php

namespace Tests\Unit\Models;

use App\Models\Debt;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_reduces_remaining_balance(): void
    {
        $user = User::factory()->create();
        $debt = Debt::create([
            'user_id' => $user->id,
            'lender_name' => 'Test Bank',
            'amount' => 10000,
            'remaining_balance' => 10000,
            'status' => 'active',
            'type' => 'personal',
        ]);

        Payment::create([
            'user_id' => $user->id,
            'debt_id' => $debt->id,
            'amount' => 3000,
            'payment_date' => '2024-01-15',
        ]);

        $debt->refresh();
        $this->assertEquals(7000.0, (float) $debt->remaining_balance);
    }

    public function test_full_payment_marks_debt_as_paid(): void
    {
        $user = User::factory()->create();
        $debt = Debt::create([
            'user_id' => $user->id,
            'lender_name' => 'Test Bank',
            'amount' => 5000,
            'remaining_balance' => 5000,
            'status' => 'active',
            'type' => 'personal',
        ]);

        Payment::create([
            'user_id' => $user->id,
            'debt_id' => $debt->id,
            'amount' => 5000,
            'payment_date' => '2024-01-15',
        ]);

        $debt->refresh();
        $this->assertEquals('paid', $debt->status);
        $this->assertEquals(0.0, (float) $debt->remaining_balance);
    }

    public function test_debt_has_payments_relationship(): void
    {
        $user = User::factory()->create();
        $debt = Debt::create([
            'user_id' => $user->id,
            'lender_name' => 'Test Bank',
            'amount' => 5000,
            'remaining_balance' => 5000,
            'status' => 'active',
            'type' => 'personal',
        ]);

        Payment::create([
            'user_id' => $user->id,
            'debt_id' => $debt->id,
            'amount' => 1000,
            'payment_date' => '2024-01-15',
        ]);

        $this->assertCount(1, $debt->payments);
    }

    public function test_multiple_payments_reduce_balance_correctly(): void
    {
        $user = User::factory()->create();
        $debt = Debt::create([
            'user_id' => $user->id,
            'lender_name' => 'Test Bank',
            'amount' => 10000,
            'remaining_balance' => 10000,
            'status' => 'active',
            'type' => 'personal',
        ]);

        Payment::create([
            'user_id' => $user->id,
            'debt_id' => $debt->id,
            'amount' => 3000,
            'payment_date' => '2024-01-01',
        ]);

        Payment::create([
            'user_id' => $user->id,
            'debt_id' => $debt->id,
            'amount' => 2000,
            'payment_date' => '2024-02-01',
        ]);

        $debt->refresh();
        $this->assertEquals(5000.0, (float) $debt->remaining_balance);
    }
}
