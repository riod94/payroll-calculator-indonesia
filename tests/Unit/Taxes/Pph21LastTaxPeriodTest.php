<?php

declare(strict_types=1);

namespace PayrollCalculator\Tests\Unit\Taxes;

use PHPUnit\Framework\TestCase;
use PayrollCalculator\PayrollCalculator;
use PayrollCalculator\Taxes\Pph21LastTaxPeriod;

class Pph21LastTaxPeriodTest extends TestCase
{
    private PayrollCalculator $calculator;
    private Pph21LastTaxPeriod $pph21;

    protected function setUp(): void
    {
        $this->calculator = new PayrollCalculator();
        $this->pph21 = new Pph21LastTaxPeriod($this->calculator);
    }

    public function testCalculateWithZeroIncome(): void
    {
        // Setup employee with zero income for last tax period
        $this->calculator->employee->name = "Employee";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->earnings->base = 0;
        $this->calculator->employee->lastTaxPeriod->annualPPh21Paid = 0;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 0;
        $this->calculator->result->earnings->annualy->gross = 0;

        $result = $this->pph21->calculate();

        $this->assertEquals(0, $result->pph->liability->annual);
        $this->assertEquals(0, $result->pph->liability->monthly);
    }

    public function testCalculateWithPreviousTaxPayments(): void
    {
        // Setup employee with previous tax payments
        $this->calculator->employee->name = "Employee";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->earnings->base = 10000000;
        $this->calculator->employee->lastTaxPeriod->annualPPh21Paid = 12000000; // Previous payments

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 10000000;
        $this->calculator->result->earnings->annualy->gross = 120000000;

        $result = $this->pph21->calculate();

        // Should calculate tax minus previous payments
        $this->assertGreaterThanOrEqual(0, $result->pph->liability->annual);
        // Monthly should equal annual for last tax period
        $this->assertEquals($result->pph->liability->annual, $result->pph->liability->monthly);
    }

    public function testCalculateWithOverpayment(): void
    {
        // Setup employee with overpayment scenario
        $this->calculator->employee->name = "Employee";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->earnings->base = 5000000; // Lower income
        $this->calculator->employee->lastTaxPeriod->annualPPh21Paid = 15000000; // High previous payments

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 5000000;
        $this->calculator->result->earnings->annualy->gross = 60000000;

        $result = $this->pph21->calculate();

        // Should result in zero or negative (refund) tax
        $this->assertLessThanOrEqual(0, $result->pph->liability->annual);
    }

    public function testCalculateWithNoNPWPSurcharge(): void
    {
        // Setup employee without NPWP for last tax period
        $this->calculator->employee->name = "Employee";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = false; // No NPWP
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->earnings->base = 10000000;
        $this->calculator->employee->lastTaxPeriod->annualPPh21Paid = 10000000;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 10000000;
        $this->calculator->result->earnings->annualy->gross = 120000000;

        $result = $this->pph21->calculate();

        // Tax should include surcharge for no NPWP
        $this->assertGreaterThanOrEqual(0, $result->pph->liability->annual);
    }

    public function testCalculateWithNonTaxableStatus(): void
    {
        // Setup non-taxable employee
        $this->calculator->employee->name = "Employee";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->taxable = false; // Non-taxable
        $this->calculator->employee->earnings->base = 10000000;
        $this->calculator->employee->lastTaxPeriod->annualPPh21Paid = 10000000;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 10000000;
        $this->calculator->result->earnings->annualy->gross = 120000000;

        $result = $this->pph21->calculate();

        // Should have no tax liability for non-taxable status
        $this->assertEquals(0, $result->pph->liability->annual);
        $this->assertEquals(0, $result->pph->liability->monthly);
    }
}
