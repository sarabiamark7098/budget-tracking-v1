<?php

namespace Database\Factories;

use App\Models\Income;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncomeFactory extends Factory
{
    protected $model = Income::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'source' => fake()->word(),
            'description' => fake()->sentence(),
            'received_at' => fake()->dateThisYear(),
            'is_recurring' => false,
        ];
    }
}
