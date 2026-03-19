<?php

namespace Database\Factories;

use App\Models\Investment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentFactory extends Factory
{
    protected $model = Investment::class;

    public function definition(): array
    {
        $amountInvested = fake()->randomFloat(2, 1000, 100000);
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(['stocks', 'crypto', 'real_estate', 'business', 'mutual_fund', 'other']),
            'amount_invested' => $amountInvested,
            'current_value' => fake()->randomFloat(2, $amountInvested * 0.5, $amountInvested * 2),
            'purchase_date' => fake()->dateThisYear(),
            'description' => fake()->sentence(),
        ];
    }
}
