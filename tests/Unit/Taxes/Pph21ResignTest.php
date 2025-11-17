<?php

declare(strict_types=1);

namespace PayrollCalculator\Tests\Unit\Taxes;

use PHPUnit\Framework\TestCase;
use PayrollCalculator\PayrollCalculator;
use PayrollCalculator\Taxes\Pph21Resign;

class Pph21ResignTest extends TestCase
{
    private PayrollCalculator $calculator;
    private Pph21Resign $pph21Resign;

    protected function setUp(): void
    {
        $this->calculator = new PayrollCalculator();
        $this->pph21Resign = new Pph21Resign($this->calculator);
    }

    public function testCalculateWithZeroResignAmount(): void
    {
        // Setup employee with zero resign amount
        $this->calculator->employee->name = "Test Employee";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->resign->severancePay = 0;

        $result = $this->pph21Resign->calculate();

        $this->assertEquals(0, $result->pphResign->liability->amount);
        $this->assertEquals(0, $result->pphResign->ptkp->amount); // PTKP not set when resign amount is 0
        $this->assertEquals("", $result->pphResign->ptkp->status);
    }

    public function testCalculateWithBasicResignAmount(): void
    {
        // Setup employee with basic resign amount
        $this->calculator->employee->name = "Test Employee";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->resign->severancePay = 100000000; // Above PTKP to generate tax

        $result = $this->pph21Resign->calculate();

        // Should have some tax liability for this resign amount
        $this->assertGreaterThan(0, $result->pphResign->liability->amount);
        $this->assertEquals(54000000, $result->pphResign->ptkp->amount);
        $this->assertEquals("TK/0", $result->pphResign->ptkp->status);
    }

    public function testCalculateWithNoNPWPSurcharge(): void
    {
        // Setup employee without NPWP
        $this->calculator->employee->name = "Test Employee";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = false; // No NPWP
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->resign->severancePay = 100000000; // Above PTKP to generate tax

        $result = $this->pph21Resign->calculate();

        // Tax should be higher due to 20% surcharge for no NPWP
        $this->assertGreaterThan(0, $result->pphResign->liability->amount);
    }

    public function testCalculateWithDifferentPTKPStatus(): void
    {
        // Test with K/1 status (married with 1 dependent)
        $this->calculator->employee->name = "Test Employee";
        $this->calculator->employee->ptkpStatus = "K/1";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->resign->severancePay = 50000000;

        $result = $this->pph21Resign->calculate();

        $this->assertEquals(63000000, $result->pphResign->ptkp->amount); // K/1 PTKP for 2024
        $this->assertEquals("K/1", $result->pphResign->ptkp->status);
    }

    public function testCalculateWithNonTaxableEmployee(): void
    {
        // Setup non-taxable employee
        $this->calculator->employee->name = "Test Employee";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->taxable = false; // Non-taxable
        $this->calculator->employee->resign->severancePay = 50000000;

        $result = $this->pph21Resign->calculate();

        // Should have no tax liability for non-taxable employee
        $this->assertEquals(0, $result->pphResign->liability->amount);
    }

    public function testCalculateWithLowResignAmount(): void
    {
        // Test with resign amount below PTKP
        $this->calculator->employee->name = "Test Employee";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->resign->severancePay = 30000000; // Below PTKP

        $result = $this->pph21Resign->calculate();

        // Should have no tax liability for amount below PTKP
        $this->assertEquals(0, $result->pphResign->liability->amount);
    }

    public function testCalculateWithHighResignAmount(): void
    {
        // Test with high resign amount
        $this->calculator->employee->name = "Test Employee";
        $this->calculator->employee->ptkpStatus = "TK/0";
        $this->calculator->employee->hasNPWP = true;
        $this->calculator->employee->permanentStatus = true;
        $this->calculator->employee->yearPeriod = 2024;
        $this->calculator->employee->monthPeriod = 12;
        $this->calculator->employee->resign->severancePay = 200000000; // 200 million severance

        $result = $this->pph21Resign->calculate();

        // Should have significant tax liability for high amount
        $this->assertGreaterThan(0, $result->pphResign->liability->amount);
        $this->assertGreaterThan(10000000, $result->pphResign->liability->amount); // Should be substantial
    }
}
