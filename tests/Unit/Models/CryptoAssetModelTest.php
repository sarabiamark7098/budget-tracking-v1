<?php

namespace Tests\Unit\Models;

use App\Models\CryptoAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CryptoAssetModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_value(): void
    {
        $user = User::factory()->create();
        $crypto = CryptoAsset::create([
            'user_id' => $user->id,
            'coin_name' => 'Bitcoin',
            'symbol' => 'BTC',
            'quantity' => 2.5,
            'buy_price' => 15000,
            'current_price' => 20000,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(50000.0, $crypto->current_value);
    }

    public function test_profit_loss(): void
    {
        $user = User::factory()->create();
        $crypto = CryptoAsset::create([
            'user_id' => $user->id,
            'coin_name' => 'Bitcoin',
            'symbol' => 'BTC',
            'quantity' => 1,
            'buy_price' => 15000,
            'current_price' => 20000,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(5000.0, $crypto->profit_loss);
    }

    public function test_negative_profit_loss(): void
    {
        $user = User::factory()->create();
        $crypto = CryptoAsset::create([
            'user_id' => $user->id,
            'coin_name' => 'Ethereum',
            'symbol' => 'ETH',
            'quantity' => 2,
            'buy_price' => 3000,
            'current_price' => 2000,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(-2000.0, $crypto->profit_loss);
    }

    public function test_profit_loss_percentage(): void
    {
        $user = User::factory()->create();
        $crypto = CryptoAsset::create([
            'user_id' => $user->id,
            'coin_name' => 'Solana',
            'symbol' => 'SOL',
            'quantity' => 10,
            'buy_price' => 100,
            'current_price' => 150,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(50.0, $crypto->profit_loss_percentage);
    }

    public function test_appended_attributes_present(): void
    {
        $user = User::factory()->create();
        $crypto = CryptoAsset::create([
            'user_id' => $user->id,
            'coin_name' => 'Cardano',
            'symbol' => 'ADA',
            'quantity' => 1000,
            'buy_price' => 1,
            'current_price' => 1.5,
            'purchase_date' => '2024-01-01',
        ]);

        $array = $crypto->toArray();
        $this->assertArrayHasKey('current_value', $array);
        $this->assertArrayHasKey('profit_loss', $array);
    }
}
