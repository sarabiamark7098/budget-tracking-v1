<?php

namespace Tests\Unit\Models;

use App\Models\Stock;
use App\Models\StockLot;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────────

    private function makeStock(array $override = []): Stock
    {
        $user = $this->createUser();

        return Stock::create(array_merge([
            'user_id'            => $user->id,
            'budget_tracking_id' => $this->getBT($user)->id,
            'symbol'             => 'AAPL',
            'company_name'       => 'Apple Inc.',
            'latest_price'       => 150,
        ], $override));
    }

    // ─── Tests ────────────────────────────────────────────────────────────────────

    public function test_current_value_calculation(): void
    {
        $stock = $this->makeStock(['latest_price' => 150]);

        StockLot::create([
            'stock_id'      => $stock->id,
            'shares'        => 10,
            'buy_price'     => 100,
            'purchase_date' => '2024-01-01',
        ]);

        $service = new StockService();
        $lots    = $service->getLots($stock);

        // current_value = 10 * 150 = 1500
        $this->assertEquals(1500.0, $lots[0]['current_value']);
    }

    public function test_profit_loss_calculation(): void
    {
        $stock = $this->makeStock(['latest_price' => 150]);

        StockLot::create([
            'stock_id'      => $stock->id,
            'shares'        => 10,
            'buy_price'     => 100,
            'purchase_date' => '2024-01-01',
        ]);

        $service = new StockService();
        $lots    = $service->getLots($stock);

        // pnl = (10 * 150) - (10 * 100) = 500
        $this->assertEquals(500.0, $lots[0]['pnl']);
    }

    public function test_profit_loss_percentage_calculation(): void
    {
        $stock = $this->makeStock([
            'symbol'       => 'GOOGL',
            'company_name' => 'Google',
            'latest_price' => 150,
        ]);

        StockLot::create([
            'stock_id'      => $stock->id,
            'shares'        => 10,
            'buy_price'     => 100,
            'purchase_date' => '2024-01-01',
        ]);

        $service = new StockService();
        $lots    = $service->getLots($stock);

        // pnl_pct = ((1500 - 1000) / 1000) * 100 = 50%
        $this->assertEquals(50.0, $lots[0]['pnl_pct']);
    }

    public function test_negative_profit_loss_for_declining_stock(): void
    {
        $stock = $this->makeStock([
            'symbol'       => 'TSLA',
            'company_name' => 'Tesla',
            'latest_price' => 150,
        ]);

        StockLot::create([
            'stock_id'      => $stock->id,
            'shares'        => 10,
            'buy_price'     => 200,
            'purchase_date' => '2024-01-01',
        ]);

        $service = new StockService();
        $lots    = $service->getLots($stock);

        // pnl = (10 * 150) - (10 * 200) = -500
        $this->assertEquals(-500.0, $lots[0]['pnl']);
    }

    public function test_profit_loss_percentage_zero_for_zero_cost_basis(): void
    {
        $stock = $this->makeStock([
            'symbol'       => 'XYZ',
            'company_name' => 'XYZ Corp',
            'latest_price' => 150,
        ]);

        StockLot::create([
            'stock_id'      => $stock->id,
            'shares'        => 10,
            'buy_price'     => 0,
            'purchase_date' => '2024-01-01',
        ]);

        $service = new StockService();
        $lots    = $service->getLots($stock);

        // cost_basis = 0, pnl_pct should be 0 (zero-division guard)
        $this->assertEquals(0.0, $lots[0]['pnl_pct']);
    }

    public function test_stock_has_lots_relationship(): void
    {
        $stock = $this->makeStock();

        StockLot::create([
            'stock_id'      => $stock->id,
            'shares'        => 5,
            'buy_price'     => 120,
            'purchase_date' => '2024-03-01',
        ]);

        $this->assertCount(1, $stock->lots);
    }
}
