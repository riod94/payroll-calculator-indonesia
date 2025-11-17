<?php

declare(strict_types=1);

namespace PayrollCalculator\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PayrollCalculator\PayrollCalculator;
use PayrollCalculator\Constants\CalculationConstants;

class PayrollCalculatorIntegrationTest extends TestCase
{
    public function testMonthlyPayrollCalculationFromReadmeExample(): void
    {
        $monthPeriod = 12;
        $yearIncomes = [];
        $annually = (object) [
            'gross' => 0,
            'jkk' => 0,
            'jkm' => 0,
            'bonus' => 0,
            'THR' => 0,
            'pph21Paid' => 0,
        ];

        for ($month = 1; $month <= $monthPeriod; $month++) {
            $calculator = new PayrollCalculator();
            
            // init State and Company Provisions
            $calculator->provisions->company->bpjsTkEmpTaxable = true; // BPJS TK Ditanggung karyawan
            $calculator->provisions->company->jkm = true;
            $calculator->provisions->company->jkk = true;
            $calculator->provisions->company->jkkRiskGrade = 0.5; // custom jkk risk grade
            
            // Use customBPJSTK
            $calculator->employee->earnings->bpjsKetenagakerjaanBase = 10000000;

            // init employee
            $calculator->employee->name = "Tuan A";
            $calculator->employee->ptkpStatus = "K/0";
            $calculator->employee->hasNPWP = true;
            $calculator->employee->permanentStatus = true;
            $calculator->employee->yearPeriod = 2024;
            $calculator->employee->monthPeriod = $month;

            $calculator->employee->earnings->base = 10000000;
            $calculator->employee->earnings->baseBeforeProrate = 10000000;
            $calculator->employee->components->allowances->set("Tunjangan Jabatan", 20000000);
            
            if (in_array($month, [2, 5])) {
                $calculator->employee->components->allowances->set("Uang Lembur", 5000000);
            }

            if ($month === 7) {
                $calculator->employee->onetime->bonus = 20000000;
            }

            if ($month === $monthPeriod) {
                $calculator->employee->onetime->holidayAllowance = 60000000;

                // Set Last Tax Period
                $calculator->employee->lastTaxPeriod->annualGross = $annually->gross;
                $calculator->employee->lastTaxPeriod->annualBonus = $annually->bonus;
                $calculator->employee->lastTaxPeriod->annualHolidayAllowance = $annually->THR;
                $calculator->employee->lastTaxPeriod->annualPPh21Paid = $annually->pph21Paid;
                $calculator->employee->lastTaxPeriod->annualJIPEmployee = 1200000; // dibayar sendiri
                $calculator->employee->lastTaxPeriod->annualZakat = 2400000; // dibayar sendiri
            }
            
            $result = $calculator->getCalculation();

            if ($result) {
                $items = (object) [
                    'month' => date("F", mktime(0, 0, 0, $month, 1, 2024)),
                    'base' => $result->earnings->base,
                    'allowance' => $calculator->employee->components->allowances->sum(),
                    'holidayAllowance' => $calculator->employee->onetime->holidayAllowance,
                    'bonus' => $calculator->employee->onetime->bonus,
                    'premi' => (object) [ 
                        'JKK' => $result->company->jkk ?? 0, 
                        'JKM' => $result->company->jkm ?? 0 
                    ],
                    'gross' => $result->earnings->monthly->gross,
                    'terCategory' => $result->taxable->pph->ter->category,
                    'pph21' => $result->taxable->pph->liability->monthly,
                ];
                
                $yearIncomes[] = $items;

                $annually->gross += $result->earnings->monthly->gross;
                $annually->jkk += $result->company->jkk;
                $annually->jkm += $result->company->jkm;
                $annually->bonus += $calculator->employee->onetime->bonus;
                $annually->THR += $calculator->employee->onetime->holidayAllowance;
                $annually->pph21Paid += $result->taxable->pph->liability->monthly;
            }
        }

        // Assertions to validate the calculation
        $this->assertNotEmpty($yearIncomes);
        $this->assertCount(12, $yearIncomes);
        
        // Check that we have meaningful calculations
        $firstMonth = $yearIncomes[0];
        $this->assertEquals(10000000, $firstMonth->base);
        $this->assertEquals(20000000, $firstMonth->allowance);
        $this->assertGreaterThan(0, $firstMonth->gross);
        
        // Check months with overtime
        $february = $yearIncomes[1]; // February (index 1)
        $this->assertEquals(25000000, $february->allowance); // Base allowance + overtime
        
        // Check month with bonus
        $july = $yearIncomes[6]; // July (index 6)
        $this->assertEquals(20000000, $july->bonus); // Performance bonus
        
        // Check last month with THR
        $december = $yearIncomes[11]; // December (index 11)
        $this->assertEquals(60000000, $december->holidayAllowance);
        
        // Check annual totals
        $this->assertGreaterThan(0, $annually->gross);
        $this->assertGreaterThan(0, $annually->pph21Paid);
    }

    public function testPayrollCalculationWithDifferentMethods(): void
    {
        $baseSalary = 10000000;
        $allowance = 2000000;

        // Test NETT method
        $calculatorNett = new PayrollCalculator();
        $calculatorNett->method = CalculationConstants::NETT;
        $calculatorNett->employee->name = "Employee NETT";
        $calculatorNett->employee->ptkpStatus = "TK/0";
        $calculatorNett->employee->hasNPWP = true;
        $calculatorNett->employee->permanentStatus = true;
        $calculatorNett->employee->yearPeriod = 2024;
        $calculatorNett->employee->monthPeriod = 1;
        $calculatorNett->employee->earnings->base = $baseSalary;
        $calculatorNett->employee->components->allowances->set("Tunjangan", $allowance);

        $resultNett = $calculatorNett->getCalculation();

        // Test GROSS method
        $calculatorGross = new PayrollCalculator();
        $calculatorGross->method = CalculationConstants::GROSS;
        $calculatorGross->employee->name = "Employee GROSS";
        $calculatorGross->employee->ptkpStatus = "TK/0";
        $calculatorGross->employee->hasNPWP = true;
        $calculatorGross->employee->permanentStatus = true;
        $calculatorGross->employee->yearPeriod = 2024;
        $calculatorGross->employee->monthPeriod = 1;
        $calculatorGross->employee->earnings->base = $baseSalary;
        $calculatorGross->employee->components->allowances->set("Tunjangan", $allowance);

        $resultGross = $calculatorGross->getCalculation();

        // Both should have valid results
        $this->assertNotNull($resultNett);
        $this->assertNotNull($resultGross);
        
        // Both methods should produce valid take-home pay
        $this->assertGreaterThan(0, $resultNett->takeHomePay);
        $this->assertGreaterThan(0, $resultGross->takeHomePay);
    }

    public function testPayrollCalculationForNonPermanentEmployee(): void
    {
        $calculator = new PayrollCalculator();
        $calculator->taxStatus = \PayrollCalculator\Types\TaxStatus::NON_PERMANENT_EMPLOYEE;
        
        $calculator->employee->name = "Contract Worker";
        $calculator->employee->ptkpStatus = "TK/0";
        $calculator->employee->hasNPWP = true;
        $calculator->employee->permanentStatus = false; // Non-permanent
        $calculator->employee->yearPeriod = 2024;
        $calculator->employee->monthPeriod = 1;
        $calculator->employee->earnings->base = 8000000;

        $result = $calculator->getCalculation();

        $this->assertNotNull($result);
        $this->assertEquals(8000000, $result->earnings->base);
        $this->assertGreaterThan(0, $result->takeHomePay);
        
        // Non-permanent employees should have no BPJS deductions
        $this->assertEquals(0, $result->company->bpjsKesehatan);
        $this->assertEquals(0, $result->company->jht);
        $this->assertEquals(0, $result->company->jip);
    }

    public function testPayrollCalculationWithResign(): void
    {
        $calculator = new PayrollCalculator();
        
        $calculator->employee->name = "Resigning Employee";
        $calculator->employee->ptkpStatus = "K/1";
        $calculator->employee->hasNPWP = true;
        $calculator->employee->permanentStatus = true;
        $calculator->employee->isResignedEmployee = true; // Enable resign processing
        $calculator->employee->yearPeriod = 2024;
        $calculator->employee->monthPeriod = 12;
        $calculator->employee->earnings->base = 10000000;
        $calculator->employee->resign->severancePay = 100000000; // Above PTKP to generate tax

        $result = $calculator->getCalculation();

        $this->assertNotNull($result);
        $this->assertEquals(100000000, $result->resign->allowances->severancePay);
        $this->assertGreaterThan(0, $result->taxable->pphResign->liability->amount);
    }

    public function testPayrollCalculationEdgeCases(): void
    {
        // Test with minimum wage
        $calculator = new PayrollCalculator();
        $calculator->employee->name = "Minimum Wage Employee";
        $calculator->employee->ptkpStatus = "TK/0";
        $calculator->employee->hasNPWP = true;
        $calculator->employee->permanentStatus = true;
        $calculator->employee->yearPeriod = 2024;
        $calculator->employee->monthPeriod = 1;
        $calculator->employee->earnings->base = 2000000; // Below tax threshold

        $result = $calculator->getCalculation();

        $this->assertNotNull($result);
        $this->assertEquals(0, $result->taxable->pph->liability->monthly); // No tax for low income
        
        // Test with very high income
        $calculatorHigh = new PayrollCalculator();
        $calculatorHigh->employee->name = "High Income Employee";
        $calculatorHigh->employee->ptkpStatus = "TK/0";
        $calculatorHigh->employee->hasNPWP = true;
        $calculatorHigh->employee->permanentStatus = true;
        $calculatorHigh->employee->yearPeriod = 2024;
        $calculatorHigh->employee->monthPeriod = 1;
        $calculatorHigh->employee->earnings->base = 50000000; // High income
        
        // Initialize result structure for PPh21 calculation
        $calculatorHigh->result->earnings->monthly->gross = 50000000;
        $calculatorHigh->result->earnings->monthly->nett = 50000000;
        $calculatorHigh->result->earnings->annualy->gross = 600000000; // Annualized
        $calculatorHigh->result->earnings->annualy->nett = 600000000; // Annualized

        $resultHigh = $calculatorHigh->getCalculation();

        $this->assertNotNull($resultHigh);
        $this->assertGreaterThan(0, $resultHigh->taxable->pph->liability->monthly); // Should have significant tax
    }
}
