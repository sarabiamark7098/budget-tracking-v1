<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'item_name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'total_cost' => fake()->randomFloat(2, 100, 50000),
            'is_installment' => false,
            'installments_paid' => 0,
            'purchase_date' => fake()->dateThisYear(),
        ];
    }

    public function installment(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_installment' => true,
            'installment_count' => 12,
            'installment_amount' => round($attributes['total_cost'] / 12, 2),
            'installments_paid' => 0,
        ]);
    }
}
