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
        // Default rate is 7.1% (2024 PAG-IBIG declared rate)
        $result = $this->service->calculate([
            'monthly_contribution' => 1000,
            'duration_years' => 5,
            'start_date' => '2024-01-01',
        ]);
        $this->assertEquals(0.071 * 100, $result['annual_dividend_rate']);
        $this->assertArrayHasKey('projected_earnings', $result);
        $this->assertGreaterThan(0, $result['projected_earnings']);
    }

    public function test_custom_dividend_rate_is_accepted_as_percentage(): void
    {
        // Passing 7.03 (%) should produce annual_dividend_rate = 7.03
        $result = $this->service->calculate([
            'monthly_contribution' => 1000,
            'duration_years'       => 1,
            'dividend_rate'        => 7.03,
        ]);
        $this->assertEquals(7.03, $result['annual_dividend_rate']);
    }

    public function test_lump_sum_replaces_january_contribution(): void
    {
        // Reference verification: ₱2,000/month for 5 years, rate 7.1%
        // Lump sums of ₱100,000 in years 3, 4, 5 (as per MP2_Calculator.txt reference)
        $result = $this->service->calculate([
            'monthly_contribution' => 2000,
            'duration_years'       => 5,
            'dividend_rate'        => 7.1,
            'start_date'           => '2024-01-01',
            'lump_sum_per_year'    => [3 => 100000, 4 => 100000, 5 => 100000],
        ]);

        // Year 3 closing balance should be ≈ 185,161.24 (per reference)
        $year3 = $result['yearly_breakdown'][2];
        $this->assertEquals(2026, $year3['calendar_year']);   // 2024 + 3 - 1
        $this->assertEqualsWithDelta(185161.24, $year3['closing_balance'], 0.05);

        // Year 5 closing balance should be ≈ 481,371.08 (per reference)
        $year5 = $result['yearly_breakdown'][4];
        $this->assertEqualsWithDelta(481371.08, $year5['closing_balance'], 0.05);

        // Total contributions: 2 years × 12 months + 3 years × (11 months + lump sum)
        // = (2000×12×2) + (2000×11×3 + 100000×3) = 48000 + 66000 + 300000 = 414000
        $this->assertEquals(414000, $result['total_contributions']);
    }

    public function test_yearly_breakdown_includes_calendar_year_when_start_date_given(): void
    {
        $result = $this->service->calculate([
            'monthly_contribution' => 1000,
            'duration_years'       => 3,
            'start_date'           => '2024-01-01',
        ]);
        $this->assertEquals(2024, $result['yearly_breakdown'][0]['calendar_year']);
        $this->assertEquals(2025, $result['yearly_breakdown'][1]['calendar_year']);
        $this->assertEquals(2026, $result['yearly_breakdown'][2]['calendar_year']);
    }

    public function test_breakdown_includes_alias_fields(): void
    {
        $result = $this->service->calculate([
            'monthly_contribution' => 1000,
            'duration_years'       => 1,
        ]);
        $row = $result['yearly_breakdown'][0];
        $this->assertArrayHasKey('dividends_earned', $row);
        $this->assertArrayHasKey('cumulative_value', $row);
        $this->assertEquals($row['total_dividends'], $row['dividends_earned']);
        $this->assertEquals($row['closing_balance'],  $row['cumulative_value']);
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
