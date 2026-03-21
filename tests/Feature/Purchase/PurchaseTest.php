<?php

namespace Tests\Feature\Purchase;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_purchase(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/purchases', [
            'item_name'      => 'Laptop',
            'total_cost'     => 50000,
            'payment_method' => 'cash',
            'purchase_date'  => '2024-01-15',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.item_name', 'Laptop');
    }

    public function test_can_create_installment_purchase(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/purchases', [
            'item_name'         => 'Refrigerator',
            'total_cost'        => 30000,
            'payment_method'    => 'credit_card',
            'installment_count' => 12,
            'installment_amount'=> 2500,
            'purchase_date'     => '2024-01-15',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_installment', true)
            ->assertJsonPath('data.installment_count', 12);
    }

    public function test_installment_requires_count_when_installment_flag_set(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/purchases', [
            'item_name'      => 'TV',
            'total_cost'     => 20000,
            'payment_method' => 'credit_card',
            'purchase_date'  => '2024-01-15',
            // installment_count intentionally omitted to trigger validation failure
        ]);
        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_can_list_purchases(): void
    {
        $user = $this->createUser();
        Purchase::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'item_name'          => 'Phone',
            'total_cost'         => 15000,
            'payment_method'     => 'cash',
            'is_installment'     => false,
            'purchase_date'      => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/purchases');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_can_update_purchase(): void
    {
        $user = $this->createUser();
        $purchase = Purchase::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'item_name'          => 'Old Item',
            'total_cost'         => 10000,
            'payment_method'     => 'cash',
            'is_installment'     => false,
            'purchase_date'      => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/purchases/{$purchase->id}", [
            'item_name'      => 'Updated Item',
            'total_cost'     => 12000,
            'purchase_date'  => '2024-01-15',
        ]);

        $response->assertOk()->assertJsonPath('data.item_name', 'Updated Item');
    }

    public function test_can_delete_purchase(): void
    {
        $user = $this->createUser();
        $purchase = Purchase::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'item_name'          => 'To Delete',
            'total_cost'         => 5000,
            'payment_method'     => 'cash',
            'is_installment'     => false,
            'purchase_date'      => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/purchases/{$purchase->id}");
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted('purchases', ['id' => $purchase->id]);
    }

    public function test_can_pay_installment(): void
    {
        $user = $this->createUser();
        $this->addIncome($user, 100000);
        $purchase = Purchase::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'item_name'          => 'Appliance',
            'total_cost'         => 24000,
            'payment_method'     => 'credit_card',
            'is_installment'     => true,
            'installment_count'  => 12,
            'installment_amount' => 2000,
            'installments_paid'  => 0,
            'purchase_date'      => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/purchases/{$purchase->id}/installment");
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'installments_paid' => 1]);
    }

    public function test_cannot_access_other_users_purchase(): void
    {
        $user  = $this->createUser();
        $other = $this->createUser();
        $purchase = Purchase::create([
            'user_id'            => $other->id,
            'budget_tracking_id' => $this->getBT($other)->id,
            'item_name'          => 'Other Item',
            'total_cost'         => 5000,
            'payment_method'     => 'cash',
            'is_installment'     => false,
            'purchase_date'      => '2024-01-15',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/purchases/{$purchase->id}");
        $response->assertStatus(403);
    }

    public function test_purchase_creation_requires_item_name(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/purchases', [
            'total_cost'     => 5000,
            'payment_method' => 'cash',
            'purchase_date'  => '2024-01-15',
        ]);
        $response->assertStatus(422)->assertJsonPath('success', false);
    }
}
