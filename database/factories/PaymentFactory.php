<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'debt_id' => Debt::factory(),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'payment_date' => fake()->dateThisYear(),
            'note' => fake()->sentence(),
        ];
    }
}
