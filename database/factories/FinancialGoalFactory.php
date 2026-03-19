<?php

namespace Database\Factories;

use App\Models\FinancialGoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialGoalFactory extends Factory
{
    protected $model = FinancialGoal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'target_amount' => fake()->randomFloat(2, 10000, 500000),
            'current_amount' => fake()->randomFloat(2, 0, 10000),
            'deadline' => fake()->dateNextYear(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => 'pending',
        ];
    }
}
