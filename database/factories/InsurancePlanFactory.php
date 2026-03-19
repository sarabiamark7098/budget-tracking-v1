<?php

namespace Database\Factories;

use App\Models\InsurancePlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsurancePlanFactory extends Factory
{
    protected $model = InsurancePlan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_name' => fake()->company(),
            'plan_name' => fake()->words(3, true),
            'coverage_type' => fake()->randomElement(['life', 'health', 'auto', 'property']),
            'coverage_amount' => fake()->randomFloat(2, 100000, 5000000),
            'premium_amount' => fake()->randomFloat(2, 500, 10000),
            'payment_frequency' => fake()->randomElement(['monthly', 'quarterly', 'semi_annually', 'annually']),
            'next_payment_date' => fake()->dateNextYear(),
            'policy_number' => fake()->numerify('POL-######'),
            'description' => fake()->sentence(),
        ];
    }
}
