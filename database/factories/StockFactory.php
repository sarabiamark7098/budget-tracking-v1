<?php

namespace Database\Factories;

use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol' => strtoupper(fake()->lexify('????')),
            'company_name' => fake()->company(),
            'shares' => fake()->randomFloat(4, 1, 1000),
            'buy_price' => fake()->randomFloat(4, 10, 500),
            'current_price' => fake()->randomFloat(4, 10, 500),
            'purchase_date' => fake()->dateThisYear(),
            'notes' => fake()->sentence(),
        ];
    }
}
