<?php

namespace Tests\Unit\Models;

use App\Models\Investment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_roi_percentage(): void
    {
        $user = User::factory()->create();
        $investment = Investment::create([
            'user_id' => $user->id,
            'name' => 'Test Investment',
            'type' => 'stocks',
            'amount_invested' => 1000,
            'current_value' => 1200,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(20.0, $investment->roi);
    }

    public function test_roi_amount(): void
    {
        $user = User::factory()->create();
        $investment = Investment::create([
            'user_id' => $user->id,
            'name' => 'Test Investment',
            'type' => 'stocks',
            'amount_invested' => 1000,
            'current_value' => 1200,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(200.0, $investment->roi_amount);
    }

    public function test_negative_roi_when_current_value_less_than_invested(): void
    {
        $user = User::factory()->create();
        $investment = Investment::create([
            'user_id' => $user->id,
            'name' => 'Loss Investment',
            'type' => 'crypto',
            'amount_invested' => 1000,
            'current_value' => 800,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(-20.0, $investment->roi);
        $this->assertEquals(-200.0, $investment->roi_amount);
    }

    public function test_roi_zero_when_amount_invested_is_zero(): void
    {
        $user = User::factory()->create();
        $investment = Investment::create([
            'user_id' => $user->id,
            'name' => 'Zero Investment',
            'type' => 'other',
            'amount_invested' => 0,
            'current_value' => 1000,
            'purchase_date' => '2024-01-01',
        ]);

        $this->assertEquals(0.0, $investment->roi);
    }

    public function test_roi_appended_attributes_present(): void
    {
        $user = User::factory()->create();
        $investment = Investment::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'type' => 'mutual_fund',
            'amount_invested' => 5000,
            'current_value' => 6000,
            'purchase_date' => '2024-01-01',
        ]);

        $array = $investment->toArray();
        $this->assertArrayHasKey('roi', $array);
        $this->assertArrayHasKey('roi_amount', $array);
    }
}
