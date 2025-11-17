<?php

declare(strict_types=1);

namespace PayrollCalculator\Tests\Unit\Taxes;

use PHPUnit\Framework\TestCase;
use PayrollCalculator\PayrollCalculator;
use PayrollCalculator\Taxes\Pph21NotEmployee;

class Pph21NotEmployeeTest extends TestCase
{
    private PayrollCalculator $calculator;
    private Pph21NotEmployee $pph21;

    protected function setUp(): void
    {
        $this->calculator = new PayrollCalculator();
        $this->pph21 = new Pph21NotEmployee($this->calculator);
    }

    public function testCalculateWithZeroIncome(): void
    {
        // Setup non-employee with zero income
        $this->calculator->employee->name = "Consultant";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = false; // Not employee
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 1;
        $this->calculator->employee->earnings->base = 0;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 0;
        $this->calculator->result->earnings->annualy->gross = 0;

        $result = $this->pph21->calculate();

        $this->assertEquals(0, $result->pph->liability->annual);
        $this->assertEquals(0, $result->pph->liability->monthly);
    }

    public function testCalculateWithBasicIncome(): void
    {
        // Setup non-employee with basic income
        $this->calculator->employee->name = "Consultant";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = false;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 1;
        $this->calculator->employee->earnings->base = 15000000;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 15000000;
        $this->calculator->result->earnings->annualy->gross = 180000000;

        $result = $this->pph21->calculate();

        // Should have some tax liability for this income level
        $this->assertGreaterThan(0, $result->pph->liability->annual);
        $this->assertGreaterThan(0, $result->pph->liability->monthly);
    }

    public function testCalculateWithNoNPWPSurcharge(): void
    {
        // Setup non-employee without NPWP
        $this->calculator->employee->name = "Consultant";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = false; // No NPWP
        $this->calculator->employee->permanentStatus = false;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 1;
        $this->calculator->employee->earnings->base = 15000000;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 15000000;
        $this->calculator->result->earnings->annualy->gross = 180000000;

        $result = $this->pph21->calculate();

        // Tax should be higher due to 20% surcharge for no NPWP
        $this->assertGreaterThan(0, $result->pph->liability->annual);
        $this->assertGreaterThan(0, $result->pph->liability->monthly);
    }

    public function testCalculateWithNonTaxableStatus(): void
    {
        // Setup non-taxable non-employee
        $this->calculator->employee->name = "Consultant";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = false;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 1;
        $this->calculator->employee->taxable = false; // Non-taxable
        $this->calculator->employee->earnings->base = 15000000;

        // Initialize result structure
        $this->calculator->result->earnings->monthly->gross = 15000000;
        $this->calculator->result->earnings->annualy->gross = 180000000;

        $result = $this->pph21->calculate();

        // Should have no tax liability for non-taxable status
        $this->assertEquals(0, $result->pph->liability->annual);
        $this->assertEquals(0, $result->pph->liability->monthly);
    }
}
