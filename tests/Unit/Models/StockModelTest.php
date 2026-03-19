<?php

namespace Tests\Unit\Models;

use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_value_calculation(): void
    {
        $user = User::factory()->create();
        $stock = Stock::create([
            'user_id' => $user->id,
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc.',
            'shares' => 10,
            'buy_price' => 100,
            'current_price' => 150,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(1500.0, $stock->current_value);
    }

    public function test_profit_loss_calculation(): void
    {
        $user = User::factory()->create();
        $stock = Stock::create([
            'user_id' => $user->id,
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc.',
            'shares' => 10,
            'buy_price' => 100,
            'current_price' => 150,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(500.0, $stock->profit_loss);
    }

    public function test_profit_loss_percentage_calculation(): void
    {
        $user = User::factory()->create();
        $stock = Stock::create([
            'user_id' => $user->id,
            'symbol' => 'GOOGL',
            'company_name' => 'Google',
            'shares' => 10,
            'buy_price' => 100,
            'current_price' => 150,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(50.0, $stock->profit_loss_percentage);
    }

    public function test_negative_profit_loss_for_declining_stock(): void
    {
        $user = User::factory()->create();
        $stock = Stock::create([
            'user_id' => $user->id,
            'symbol' => 'TSLA',
            'company_name' => 'Tesla',
            'shares' => 10,
            'buy_price' => 200,
            'current_price' => 150,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(-500.0, $stock->profit_loss);
    }

    public function test_profit_loss_percentage_zero_for_zero_cost_basis(): void
    {
        $user = User::factory()->create();
        $stock = Stock::create([
            'user_id' => $user->id,
            'symbol' => 'XYZ',
            'company_name' => 'XYZ Corp',
            'shares' => 10,
            'buy_price' => 0,
            'current_price' => 150,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(0.0, $stock->profit_loss_percentage);
    }
}
