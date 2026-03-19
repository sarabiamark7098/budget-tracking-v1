<?php

namespace Tests\Feature\Stock;

use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_stock(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/stocks', [
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc.',
            'shares' => 10,
            'buy_price' => 150,
            'current_price' => 175,
            'purchase_date' => '2024-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.symbol', 'AAPL');
    }

    public function test_can_list_stocks(): void
    {
        $user = User::factory()->create();
        Stock::create([
            'user_id' => $user->id,
            'symbol' => 'GOOGL',
            'company_name' => 'Google',
            'shares' => 5,
            'buy_price' => 100,
            'current_price' => 110,
            'purchase_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/stocks');
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_portfolio_summary_includes_total_value(): void
    {
        $user = User::factory()->create();
        Stock::create([
            'user_id' => $user->id,
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc.',
            'shares' => 10,
            'buy_price' => 150,
            'current_price' => 175,
            'purchase_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/stocks/portfolio');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_current_value']]);
    }

    public function test_can_update_stock_price(): void
    {
        $user = User::factory()->create();
        $stock = Stock::create([
            'user_id' => $user->id,
            'symbol' => 'TSLA',
            'company_name' => 'Tesla',
            'shares' => 10,
            'buy_price' => 200,
            'current_price' => 210,
            'purchase_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/stocks/{$stock->id}", [
            'symbol' => 'TSLA',
            'company_name' => 'Tesla',
            'shares' => 10,
            'buy_price' => 200,
            'current_price' => 250,
            'purchase_date' => '2024-01-01',
        ]);

        $response->assertOk()->assertJsonPath('data.current_price', '250.0000');
    }

    public function test_profit_loss_calculated_correctly(): void
    {
        $user = User::factory()->create();
        $stock = Stock::create([
            'user_id' => $user->id,
            'symbol' => 'META',
            'company_name' => 'Meta',
            'shares' => 10,
            'buy_price' => 100,
            'current_price' => 150,
            'purchase_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/stocks/{$stock->id}");
        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(500.0, $data['profit_loss']);
    }

    public function test_cannot_access_other_users_stock(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $stock = Stock::create([
            'user_id' => $otherUser->id,
            'symbol' => 'XYZ',
            'company_name' => 'XYZ Corp',
            'shares' => 10,
            'buy_price' => 100,
            'current_price' => 110,
            'purchase_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/stocks/{$stock->id}");
        $response->assertStatus(403);
    }

    public function test_stock_validation_requires_symbol(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/stocks', [
            'company_name' => 'Missing Symbol Corp',
            'shares' => 10,
            'buy_price' => 100,
            'current_price' => 110,
            'purchase_date' => '2024-01-01',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }
}
