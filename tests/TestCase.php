<?php

declare(strict_types=1);

namespace PayrollCalculator\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a PayrollCalculator instance with default test configuration
     */
    protected function createCalculator(): \PayrollCalculator\PayrollCalculator
    {
        $calculator = new \PayrollCalculator\PayrollCalculator();

        // Set default test values
        $calculator->employee->name = "Test Employee";
        $calculator->employee->ptkpStatus = "TK/0";
        $calculator->employee->hasNPWP = true;
        $calculator->employee->permanentStatus = true;
        $calculator->employee->yearPeriod = 2024;
        $calculator->employee->monthPeriod = 1;
        $calculator->employee->taxable = true;

        return $calculator;
    }

    /**
     * Create a PayrollCalculator instance for non-permanent employee
     */
    protected function createNonPermanentCalculator(): \PayrollCalculator\PayrollCalculator
    {
        $calculator = $this->createCalculator();
        $calculator->employee->permanentStatus = false;
        $calculator->taxStatus = \PayrollCalculator\Types\TaxStatus::NON_PERMANENT_EMPLOYEE;

        return $calculator;
    }

    /**
     * Create a PayrollCalculator instance for commissioner
     */
    protected function createCommissionerCalculator(): \PayrollCalculator\PayrollCalculator
    {
        $calculator = $this->createCalculator();
        $calculator->taxStatus = \PayrollCalculator\Types\TaxStatus::BOARD_OF_COMMISSIONERS;

        return $calculator;
    }

    /**
     * Create a PayrollCalculator instance for resign calculation
     */
    protected function createResignCalculator(float $resignAmount = 50000000): \PayrollCalculator\PayrollCalculator
    {
        $calculator = $this->createCalculator();
        $calculator->employee->resign->amount = $resignAmount;
        $calculator->employee->monthPeriod = 12; // Usually December for resign

        return $calculator;
    }

    /**
     * Helper method to initialize result structure for tax calculations
     */
    protected function initializeResultStructure(\PayrollCalculator\PayrollCalculator $calculator, float $monthlyGross = 10000000): void
    {
        $calculator->result->earnings->monthly->gross = $monthlyGross;
        $calculator->result->earnings->monthly->nett = $monthlyGross;
        $calculator->result->earnings->annualy->gross = $monthlyGross * $calculator->employee->monthMultiplier;
        $calculator->result->earnings->annualy->nett = $monthlyGross * $calculator->employee->monthMultiplier;
    }

    /**
     * Assert that tax calculation is reasonable (not negative, not extremely high)
     */
    protected function assertReasonableTax(float $tax, string $message = ''): void
    {
        $this->assertGreaterThanOrEqual(0, $tax, $message ?: 'Tax should not be negative');
        $this->assertLessThan(100000000, $tax, $message ?: 'Tax seems unreasonably high');
    }

    /**
     * Assert that take-home pay is reasonable
     */
    protected function assertReasonableTakeHomePay(float $takeHomePay, float $grossIncome, string $message = ''): void
    {
        $this->assertGreaterThan(0, $takeHomePay, $message ?: 'Take-home pay should be positive');
        $this->assertLessThanOrEqual($grossIncome * 1.2, $takeHomePay, $message ?: 'Take-home pay should not exceed gross income by more than 20% (allowing for gross-up method)');
    }
}
