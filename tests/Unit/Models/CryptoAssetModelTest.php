<?php

namespace Tests\Unit\Models;

use App\Models\CryptoAsset;
use App\Models\CryptoLot;
use App\Services\CryptoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CryptoAssetModelTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────────

    private function makeCrypto(array $override = []): CryptoAsset
    {
        $user = $this->createUser();

        return CryptoAsset::create(array_merge([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'coin_name'          => 'Bitcoin',
            'symbol'             => 'BTC',
            'latest_price'       => 20000,
        ], $override));
    }

    // ─── Tests ────────────────────────────────────────────────────────────────────

    public function test_current_value(): void
    {
        $crypto = $this->makeCrypto(['latest_price' => 20000]);

        CryptoLot::create([
            'crypto_asset_id' => $crypto->id,
            'quantity'        => 2.5,
            'buy_price'       => 15000,
            'purchase_date'   => '2024-01-01',
        ]);

        $service = new CryptoService();
        $lots    = $service->getLots($crypto);

        // current_value = 2.5 * 20000 = 50000
        $this->assertEquals(50000.0, $lots[0]['current_value']);
    }

    public function test_profit_loss(): void
    {
        $crypto = $this->makeCrypto(['latest_price' => 20000]);

        CryptoLot::create([
            'crypto_asset_id' => $crypto->id,
            'quantity'        => 1,
            'buy_price'       => 15000,
            'purchase_date'   => '2024-01-01',
        ]);

        $service = new CryptoService();
        $lots    = $service->getLots($crypto);

        // pnl = (1 * 20000) - (1 * 15000) = 5000
        $this->assertEquals(5000.0, $lots[0]['pnl']);
    }

    public function test_negative_profit_loss(): void
    {
        $crypto = $this->makeCrypto([
            'coin_name'    => 'Ethereum',
            'symbol'       => 'ETH',
            'latest_price' => 2000,
        ]);

        CryptoLot::create([
            'crypto_asset_id' => $crypto->id,
            'quantity'        => 2,
            'buy_price'       => 3000,
            'purchase_date'   => '2024-01-01',
        ]);

        $service = new CryptoService();
        $lots    = $service->getLots($crypto);

        // pnl = (2 * 2000) - (2 * 3000) = -2000
        $this->assertEquals(-2000.0, $lots[0]['pnl']);
    }

    public function test_profit_loss_percentage(): void
    {
        $crypto = $this->makeCrypto([
            'coin_name'    => 'Solana',
            'symbol'       => 'SOL',
            'latest_price' => 150,
        ]);

        CryptoLot::create([
            'crypto_asset_id' => $crypto->id,
            'quantity'        => 10,
            'buy_price'       => 100,
            'purchase_date'   => '2024-01-01',
        ]);

        $service = new CryptoService();
        $lots    = $service->getLots($crypto);

        // pnl_pct = ((1500 - 1000) / 1000) * 100 = 50%
        $this->assertEquals(50.0, $lots[0]['pnl_pct']);
    }

    public function test_lot_fields_present(): void
    {
        $crypto = $this->makeCrypto([
            'coin_name'    => 'Cardano',
            'symbol'       => 'ADA',
            'latest_price' => 1.5,
        ]);

        CryptoLot::create([
            'crypto_asset_id' => $crypto->id,
            'quantity'        => 1000,
            'buy_price'       => 1,
            'purchase_date'   => '2024-01-01',
        ]);

        $service = new CryptoService();
        $lots    = $service->getLots($crypto);

        $this->assertArrayHasKey('current_value', $lots[0]);
        $this->assertArrayHasKey('pnl',           $lots[0]);
        $this->assertArrayHasKey('pnl_pct',       $lots[0]);
        $this->assertArrayHasKey('cost_basis',     $lots[0]);
    }

    public function test_crypto_asset_has_lots_relationship(): void
    {
        $crypto = $this->makeCrypto();

        CryptoLot::create([
            'crypto_asset_id' => $crypto->id,
            'quantity'        => 0.5,
            'buy_price'       => 30000,
            'purchase_date'   => '2024-06-01',
        ]);

        $this->assertCount(1, $crypto->lots);
    }
}
