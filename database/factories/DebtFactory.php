<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DebtFactory extends Factory
{
    protected $model = Debt::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 1000, 100000);
        return [
            'user_id' => User::factory(),
            'lender_name' => fake()->name(),
            'amount' => $amount,
            'remaining_balance' => $amount,
            'interest_rate' => fake()->randomFloat(2, 0, 20),
            'due_date' => fake()->dateNextYear(),
            'description' => fake()->sentence(),
            'status' => 'active',
            'type' => 'personal',
        ];
    }

    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'business',
            'business_name' => fake()->company(),
        ]);
    }
}
