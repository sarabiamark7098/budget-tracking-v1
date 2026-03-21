<?php

namespace Tests\Feature\Stock;

use App\Models\Stock;
use App\Models\StockLot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_stock(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/stocks', [
            'symbol'       => 'AAPL',
            'company_name' => 'Apple Inc.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.symbol', 'AAPL');
    }

    public function test_can_list_stocks(): void
    {
        $user = $this->createUser();
        Stock::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'symbol'             => 'GOOGL',
            'company_name'       => 'Google',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/stocks');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_portfolio_summary_includes_total_value(): void
    {
        $user = $this->createUser();
        $stock = Stock::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'symbol'             => 'AAPL',
            'company_name'       => 'Apple Inc.',
            'latest_price'       => 175,
        ]);
        StockLot::create([
            'stock_id'      => $stock->id,
            'shares'        => 10,
            'buy_price'     => 150,
            'purchase_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/stocks/portfolio');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_current_value']]);
    }

    public function test_can_update_stock_latest_price(): void
    {
        $user = $this->createUser();
        $stock = Stock::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'symbol'             => 'TSLA',
            'company_name'       => 'Tesla',
            'latest_price'       => 210,
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/stocks/{$stock->id}", [
            'latest_price' => 250,
        ]);

        $response->assertOk()->assertJsonPath('data.latest_price', '250.0000');
    }

    public function test_portfolio_profit_loss_calculated_correctly(): void
    {
        $user = $this->createUser();
        $stock = Stock::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'symbol'             => 'META',
            'company_name'       => 'Meta',
            'latest_price'       => 150,
        ]);
        StockLot::create([
            'stock_id'      => $stock->id,
            'shares'        => 10,
            'buy_price'     => 100,
            'purchase_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/stocks/portfolio');
        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(500.0, $data['total_profit_loss']);
    }

    public function test_cannot_access_other_users_stock(): void
    {
        $user      = $this->createUser();
        $otherUser = $this->createUser();
        $stock = Stock::create([
            'user_id'            => $otherUser->id,
            'budget_tracking_id' => $this->getBT($otherUser)->id,
            'symbol'             => 'XYZ',
            'company_name'       => 'XYZ Corp',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/stocks/{$stock->id}");
        $response->assertStatus(403);
    }

    public function test_stock_validation_requires_symbol(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/stocks', [
            'company_name' => 'Missing Symbol Corp',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }
}
