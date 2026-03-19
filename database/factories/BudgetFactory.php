<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        $startDate = fake()->dateThisMonth();
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'period' => fake()->randomElement(['weekly', 'monthly', 'yearly']),
            'start_date' => $startDate,
            'end_date' => date('Y-m-d', strtotime($startDate . ' +1 month')),
            'alert_threshold' => 80,
        ];
    }
}
