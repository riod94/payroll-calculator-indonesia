<?php

declare(strict_types=1);

namespace PayrollCalculator\Tests\Unit\Taxes;

use PHPUnit\Framework\TestCase;
use PayrollCalculator\PayrollCalculator;
use PayrollCalculator\Taxes\Pph21;
use PayrollCalculator\Types\TaxStatus;

class Pph21Test extends TestCase
{
    private const TEST_EMPLOYEE_NAME = "Test Employee";
    
    private PayrollCalculator $calculator;
    private Pph21 $pph21;

    protected function setUp(): void
    {
        $this->calculator = new PayrollCalculator();
        $this->pph21 = new Pph21($this->calculator);
    }

    public function testCalculateWithZeroIncome(): void
    {
        // Setup employee with zero income
        $this->calculator->employee->name = self::TEST_EMPLOYEE_NAME;
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 1;
        $this->calculator->employee->earnings->base = 0;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 0;
        $this->calculator->result->earnings->monthly->nett = 0;
        $this->calculator->result->earnings->annualy->gross = 0;
        $this->calculator->result->earnings->annualy->nett = 0;

        $result = $this->pph21->calculate();

        $this->assertEquals(0, $result->pph->liability->annual);
        $this->assertEquals(0, $result->pph->liability->monthly);
        $this->assertEquals(0, $result->pph->ptkp->amount); // PTKP not set when income is 0
        $this->assertEquals("", $result->pph->ptkp->status);
    }

    public function testCalculateWithBasicIncome(): void
    {
        // Setup employee with basic income
        $this->calculator->employee->name = self::TEST_EMPLOYEE_NAME;
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 1;
        $this->calculator->employee->earnings->base = 10000000;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 10000000;
        $this->calculator->result->earnings->monthly->nett = 10000000;
        $this->calculator->result->earnings->annualy->gross = 120000000;
        $this->calculator->result->earnings->annualy->nett = 120000000;

        $result = $this->pph21->calculate();

        // Should have some tax liability for this income level
        $this->assertGreaterThan(0, $result->pph->liability->annual);
        $this->assertGreaterThan(0, $result->pph->liability->monthly);
        $this->assertEquals(54000000, $result->pph->ptkp->amount);
        $this->assertEquals("TK/0", $result->pph->ptkp->status);
    }

    public function testCalculateWithNoNPWPSurcharge(): void
    {
        // Setup employee without NPWP
        $this->calculator->employee->name = self::TEST_EMPLOYEE_NAME;
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = false; // No NPWP
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 1;
        $this->calculator->employee->earnings->base = 10000000;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 10000000;
        $this->calculator->result->earnings->monthly->nett = 10000000;
        $this->calculator->result->earnings->annualy->gross = 120000000;
        $this->calculator->result->earnings->annualy->nett = 120000000;

        $result = $this->pph21->calculate();

        // Tax should be higher due to 20% surcharge for no NPWP
        $this->assertGreaterThan(0, $result->pph->liability->annual);
        $this->assertGreaterThan(0, $result->pph->liability->monthly);
    }

    public function testCalculateWithDifferentPTKPStatus(): void
    {
        // Test with K/1 status (married with 1 dependent)
        $this->calculator->employee->name = self::TEST_EMPLOYEE_NAME;
        $this->calculator->employee->ptkpStatus = "K/1";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 1;
        $this->calculator->employee->earnings->base = 10000000;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 10000000;
        $this->calculator->result->earnings->monthly->nett = 10000000;
        $this->calculator->result->earnings->annualy->gross = 120000000;
        $this->calculator->result->earnings->annualy->nett = 120000000;

        $result = $this->pph21->calculate();

        $this->assertEquals(63000000, $result->pph->ptkp->amount); // K/1 PTKP for 2024
        $this->assertEquals("K/1", $result->pph->ptkp->status);
    }

    public function testCalculateWithNonTaxableEmployee(): void
    {
        // Setup non-taxable employee
        $this->calculator->employee->name = self::TEST_EMPLOYEE_NAME;
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 1;
        $this->calculator->employee->taxable = false; // Non-taxable
        $this->calculator->employee->earnings->base = 10000000;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 10000000;
        $this->calculator->result->earnings->monthly->nett = 10000000;
        $this->calculator->result->earnings->annualy->gross = 120000000;
        $this->calculator->result->earnings->annualy->nett = 120000000;

        $result = $this->pph21->calculate();

        // Should have no tax liability for non-taxable employee
        $this->assertEquals(0, $result->pph->liability->annual);
        $this->assertEquals(0, $result->pph->liability->monthly);
    }

    public function testCalculateWithLowIncome(): void
    {
        // Test with income below PPh21 threshold
        $this->calculator->employee->name = self::TEST_EMPLOYEE_NAME;
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 1;
        $this->calculator->employee->earnings->base = 3000000; // Low income

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 3000000;
        $this->calculator->result->earnings->monthly->nett = 3000000;
        $this->calculator->result->earnings->annualy->gross = 36000000;
        $this->calculator->result->earnings->annualy->nett = 36000000;

        $result = $this->pph21->calculate();

        // Should have no tax liability for low income
        $this->assertEquals(0, $result->pph->liability->annual);
        $this->assertEquals(0, $result->pph->liability->monthly);
    }
}
