<?php

namespace Database\Factories;

use App\Models\FinancialPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialPlanFactory extends Factory
{
    protected $model = FinancialPlan::class;

    public function definition(): array
    {
        $startDate = fake()->dateThisYear();
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'monthly_income_target' => fake()->randomFloat(2, 10000, 100000),
            'monthly_expense_limit' => fake()->randomFloat(2, 5000, 50000),
            'savings_target' => fake()->randomFloat(2, 50000, 500000),
            'start_date' => $startDate,
            'end_date' => date('Y-m-d', strtotime($startDate . ' +1 year')),
            'status' => 'active',
        ];
    }
}
