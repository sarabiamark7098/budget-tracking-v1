<?php

namespace Database\Factories;

use App\Models\MP2Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MP2PlanFactory extends Factory
{
    protected $model = MP2Plan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'monthly_contribution' => fake()->randomFloat(2, 500, 5000),
            'duration_years' => fake()->numberBetween(1, 10),
            'start_date' => fake()->dateThisYear(),
            'projected_earnings' => fake()->randomFloat(2, 1000, 100000),
            'total_contributions' => fake()->randomFloat(2, 5000, 200000),
            'notes' => fake()->sentence(),
        ];
    }
}
