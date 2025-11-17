<?php

declare(strict_types=1);

namespace PayrollCalculator\Tests\Unit\DataStructures;

use PHPUnit\Framework\TestCase;
use PayrollCalculator\DataStructures\Employee;
use PayrollCalculator\DataStructures\Earnings;
use PayrollCalculator\DataStructures\Components;
use PayrollCalculator\DataStructures\Onetime;
use PayrollCalculator\DataStructures\Overtimes;
use PayrollCalculator\DataStructures\Resign;
use PayrollCalculator\DataStructures\LastTaxPeriod;

class EmployeeTest extends TestCase
{
    public function testEmployeeCreationWithDefaultValues(): void
    {
        $employee = new Employee();

        $this->assertEquals('', $employee->name);
        $this->assertTrue($employee->taxable);
        $this->assertTrue($employee->permanentStatus);
        $this->assertFalse($employee->hasNPWP); // Default is false
        $this->assertFalse($employee->maritalStatus);
        $this->assertEquals(0, $employee->numberOfDependentsFamily);
        $this->assertEquals(3, $employee->jhtPayor);
        $this->assertEquals(3, $employee->bpjsKesPayor);
        $this->assertEquals(3, $employee->jpPayor);
        $this->assertEquals(12, $employee->monthMultiplier);
        $this->assertEquals(1, $employee->rateMultiplier);
        $this->assertFalse($employee->isNewlyJoinedEmployee);
        $this->assertFalse($employee->isResignedEmployee);
        
        // Check current year and month are set (constructor initializes them to current date)
        $this->assertEquals((int)date('Y'), $employee->yearPeriod);
        $this->assertEquals((int)date('n'), $employee->monthPeriod);
    }

    public function testEmployeeCreationWithCustomValues(): void
    {
        $employee = new Employee(
            name: 'John Doe',
            taxable: false,
            permanentStatus: false,
            yearPeriod: 2024,
            monthPeriod: 6,
            ptkpStatus: 'K/2',
            hasNPWP: false,
            maritalStatus: true,
            numberOfDependentsFamily: 2,
            jhtPayor: 2,
            bpjsKesPayor: 2,
            jpPayor: 2,
            monthMultiplier: 13,
            rateMultiplier: 2,
            isNewlyJoinedEmployee: true,
            isResignedEmployee: true
        );

        $this->assertEquals('John Doe', $employee->name);
        $this->assertFalse($employee->taxable);
        $this->assertFalse($employee->permanentStatus);
        $this->assertEquals(2024, $employee->yearPeriod);
        $this->assertEquals(6, $employee->monthPeriod);
        $this->assertEquals('K/2', $employee->ptkpStatus);
        $this->assertFalse($employee->hasNPWP);
        $this->assertTrue($employee->maritalStatus);
        $this->assertEquals(2, $employee->numberOfDependentsFamily);
        $this->assertEquals(2, $employee->jhtPayor);
        $this->assertEquals(2, $employee->bpjsKesPayor);
        $this->assertEquals(2, $employee->jpPayor);
        $this->assertEquals(13, $employee->monthMultiplier);
        $this->assertEquals(2, $employee->rateMultiplier);
        $this->assertTrue($employee->isNewlyJoinedEmployee);
        $this->assertTrue($employee->isResignedEmployee);
    }

    public function testEmployeeComponentsExist(): void
    {
        $employee = new Employee();
        
        // Test that components are initialized with correct types
        $this->assertInstanceOf(Earnings::class, $employee->earnings);
        $this->assertInstanceOf(Components::class, $employee->components);
        $this->assertInstanceOf(Onetime::class, $employee->onetime);
        $this->assertInstanceOf(Overtimes::class, $employee->overtimes);
        $this->assertInstanceOf(Resign::class, $employee->resign);
        $this->assertInstanceOf(LastTaxPeriod::class, $employee->lastTaxPeriod);
    }

    public function testEmployeeWithCustomComponents(): void
    {
        // Test with custom components now that classes are available
        $customEarnings = new Earnings(base: 15000000);
        $customResign = new Resign();

        $employee = new Employee(
            earnings: $customEarnings,
            resign: $customResign
        );

        $this->assertEquals(15000000, $employee->earnings->base);
        $this->assertInstanceOf(Resign::class, $employee->resign);
    }
}
