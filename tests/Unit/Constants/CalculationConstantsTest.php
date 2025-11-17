<?php

declare(strict_types=1);

namespace PayrollCalculator\Tests\Unit\Constants;

use PHPUnit\Framework\TestCase;
use PayrollCalculator\Constants\CalculationConstants;

class CalculationConstantsTest extends TestCase
{
    public function testCalculationMethods(): void
    {
        $this->assertEquals('NETT', CalculationConstants::NETT);
        $this->assertEquals('GROSS', CalculationConstants::GROSS);
        $this->assertEquals('GROSSUP', CalculationConstants::GROSS_UP);
    }

    public function testCalculationMethodValuesAreStrings(): void
    {
        $this->assertIsString(CalculationConstants::NETT);
        $this->assertIsString(CalculationConstants::GROSS);
        $this->assertIsString(CalculationConstants::GROSS_UP);
    }

    public function testCalculationMethodsAreUnique(): void
    {
        $methods = [
            CalculationConstants::NETT,
            CalculationConstants::GROSS,
            CalculationConstants::GROSS_UP
        ];

        $uniqueMethods = array_unique($methods);
        $this->assertEquals(count($methods), count($uniqueMethods), 'All calculation methods should be unique');
    }

    public function testCalculationMethodsAreNotEmpty(): void
    {
        $this->assertNotEmpty(CalculationConstants::NETT);
        $this->assertNotEmpty(CalculationConstants::GROSS);
        $this->assertNotEmpty(CalculationConstants::GROSS_UP);
    }
}
