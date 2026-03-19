<?php

namespace Tests\Feature\Crypto;

use App\Models\CryptoAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CryptoTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_crypto_asset(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/crypto', [
            'coin_name' => 'Bitcoin',
            'symbol' => 'BTC',
            'quantity' => 0.5,
            'buy_price' => 40000,
            'current_price' => 50000,
            'purchase_date' => '2024-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.symbol', 'BTC');
    }

    public function test_can_get_crypto_portfolio(): void
    {
        $user = User::factory()->create();
        CryptoAsset::create([
            'user_id' => $user->id,
            'coin_name' => 'Bitcoin',
            'symbol' => 'BTC',
            'quantity' => 1,
            'buy_price' => 40000,
            'current_price' => 50000,
            'purchase_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/crypto/portfolio');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_current_value']]);
    }

    public function test_profit_loss_is_calculated(): void
    {
        $user = User::factory()->create();
        $crypto = CryptoAsset::create([
            'user_id' => $user->id,
            'coin_name' => 'Ethereum',
            'symbol' => 'ETH',
            'quantity' => 2,
            'buy_price' => 2000,
            'current_price' => 3000,
            'purchase_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/crypto/{$crypto->id}");
        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(2000.0, $data['profit_loss']);
    }

    public function test_can_update_crypto_price(): void
    {
        $user = User::factory()->create();
        $crypto = CryptoAsset::create([
            'user_id' => $user->id,
            'coin_name' => 'Bitcoin',
            'symbol' => 'BTC',
            'quantity' => 1,
            'buy_price' => 40000,
            'current_price' => 45000,
            'purchase_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/crypto/{$crypto->id}", [
            'coin_name' => 'Bitcoin',
            'symbol' => 'BTC',
            'quantity' => 1,
            'buy_price' => 40000,
            'current_price' => 60000,
            'purchase_date' => '2024-01-01',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $data = $response->json('data');
        $this->assertEquals('60000.00000000', $data['current_price']);
    }

    public function test_cannot_access_other_users_crypto(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $crypto = CryptoAsset::create([
            'user_id' => $otherUser->id,
            'coin_name' => 'Solana',
            'symbol' => 'SOL',
            'quantity' => 10,
            'buy_price' => 100,
            'current_price' => 150,
            'purchase_date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/crypto/{$crypto->id}");
        $response->assertStatus(403);
    }

    public function test_crypto_validation_requires_quantity(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/crypto', [
            'coin_name' => 'Bitcoin',
            'symbol' => 'BTC',
            'buy_price' => 40000,
            'current_price' => 50000,
            'purchase_date' => '2024-01-01',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }
}
