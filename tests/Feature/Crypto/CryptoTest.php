<?php

namespace Tests\Feature\Crypto;

use App\Models\CryptoAsset;
use App\Models\CryptoLot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CryptoTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_crypto_asset(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/crypto', [
            'coin_name' => 'Bitcoin',
            'symbol'    => 'BTC',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.symbol', 'BTC');
    }

    public function test_can_get_crypto_portfolio(): void
    {
        $user = $this->createUser();
        $crypto = CryptoAsset::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'coin_name'          => 'Bitcoin',
            'symbol'             => 'BTC',
            'latest_price'       => 50000,
        ]);
        CryptoLot::create([
            'crypto_asset_id' => $crypto->id,
            'quantity'        => 1,
            'buy_price'       => 40000,
            'purchase_date'   => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/crypto/portfolio');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_current_value']]);
    }

    public function test_portfolio_profit_loss_is_calculated(): void
    {
        $user = $this->createUser();
        $crypto = CryptoAsset::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'coin_name'          => 'Ethereum',
            'symbol'             => 'ETH',
            'latest_price'       => 3000,
        ]);
        CryptoLot::create([
            'crypto_asset_id' => $crypto->id,
            'quantity'        => 2,
            'buy_price'       => 2000,
            'purchase_date'   => '2024-01-01',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/crypto/portfolio');
        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(2000.0, $data['total_profit_loss']);
    }

    public function test_can_update_crypto_latest_price(): void
    {
        $user = $this->createUser();
        $crypto = CryptoAsset::create([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'coin_name'          => 'Bitcoin',
            'symbol'             => 'BTC',
            'latest_price'       => 45000,
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/crypto/{$crypto->id}/price", [
            'latest_price' => 60000,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $data = $response->json('data');
        $this->assertEquals('60000.00000000', $data['latest_price']);
    }

    public function test_cannot_access_other_users_crypto(): void
    {
        $user      = $this->createUser();
        $otherUser = $this->createUser();
        $crypto = CryptoAsset::create([
            'user_id'            => $otherUser->id,
            'budget_tracking_id' => $this->getBT($otherUser)->id,
            'coin_name'          => 'Solana',
            'symbol'             => 'SOL',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/crypto/{$crypto->id}");
        $response->assertStatus(403);
    }

    public function test_crypto_validation_requires_symbol(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/crypto', [
            'coin_name' => 'Bitcoin',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }
}
