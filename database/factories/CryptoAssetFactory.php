<?php

namespace Database\Factories;

use App\Models\CryptoAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CryptoAssetFactory extends Factory
{
    protected $model = CryptoAsset::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'coin_name' => fake()->randomElement(['Bitcoin', 'Ethereum', 'Solana', 'Cardano']),
            'symbol' => strtoupper(fake()->lexify('???')),
            'quantity' => fake()->randomFloat(8, 0.001, 100),
            'buy_price' => fake()->randomFloat(8, 100, 50000),
            'current_price' => fake()->randomFloat(8, 100, 50000),
            'purchase_date' => fake()->dateThisYear(),
            'notes' => fake()->sentence(),
        ];
    }
}
