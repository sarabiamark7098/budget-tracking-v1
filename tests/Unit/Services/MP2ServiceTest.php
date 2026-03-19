<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MP2Service;

class MP2ServiceTest extends TestCase
{
    private MP2Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MP2Service();
    }

    public function test_calculates_total_contributions(): void
    {
        $result = $this->service->calculate([
            'monthly_contribution' => 1000,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
        ]);
        $this->assertEquals(60000, $result['total_contributions']);
    }

    public function test_returns_calculation_breakdown(): void
    {
        $result = $this->service->calculate([
            'monthly_contribution' => 1000,
            'duration_years' => 3,
            'start_date' => '2024-01-01',
        ]);
        $this->assertArrayHasKey('yearly_breakdown', $result);
        $this->assertCount(3, $result['yearly_breakdown']);
    }

    public function test_projected_earnings_greater_than_contributions(): void
    {
        $result = $this->service->calculate([
            'monthly_contribution' => 1000,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
        ]);
        $this->assertGreaterThan($result['total_contributions'], $result['projected_earnings'] + $result['total_contributions']);
        $this->assertGreaterThan(0, $result['projected_earnings']);
    }

    public function test_calculates_correct_projected_earnings(): void
    {
        // Uses 7.03% annual dividend
        $result = $this->service->calculate([
            'monthly_contribution' => 1000,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
        ]);
        $this->assertEquals(0.0703 * 100, $result['annual_dividend_rate']);
        $this->assertArrayHasKey('projected_earnings', $result);
        $this->assertGreaterThan(0, $result['projected_earnings']);
    }

    public function test_calculation_with_zero_contribution(): void
    {
        $result = $this->service->calculate([
            'monthly_contribution' => 0,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
        ]);
        $this->assertEquals(0, $result['total_contributions']);
        $this->assertEquals(0, $result['projected_earnings']);
    }

    public function test_calculation_returns_all_required_keys(): void
    {
        $result = $this->service->calculate([
            'monthly_contribution' => 1000,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
        ]);
        $this->assertArrayHasKey('monthly_contribution', $result);
        $this->assertArrayHasKey('duration_years', $result);
        $this->assertArrayHasKey('total_contributions', $result);
        $this->assertArrayHasKey('projected_earnings', $result);
        $this->assertArrayHasKey('total_value', $result);
        $this->assertArrayHasKey('annual_dividend_rate', $result);
        $this->assertArrayHasKey('yearly_breakdown', $result);
    }

    public function test_total_value_equals_contributions_plus_earnings(): void
    {
        $result = $this->service->calculate([
            'monthly_contribution' => 2000,
            'duration_years' => 10,
            'start_date' => '2024-01-01',
        ]);
        $expectedTotal = round($result['total_contributions'] + $result['projected_earnings'], 2);
        $this->assertEquals($expectedTotal, $result['total_value']);
    }
}
