# Payroll Calculator PHP

A comprehensive payroll calculator library for PHP projects with Indonesian tax calculations (PPh21, PPh23, PPh26).

## Features

-  **PPh21 Calculation**: Complete Indonesian income tax calculation for employees
-  **PPh23 Calculation**: Withholding tax for services, rentals, and other payments (2025 compliant)
-  **PPh26 Calculation**: Final withholding tax with 20% rate (2025 compliant)
-  **Multiple Calculation Methods**: NETT, GROSS, and GROSS_UP support
-  **Overtime Calculation**: Complete overtime payment calculation
-  **BPJS Integration**: BPJS Kesehatan and BPJS Ketenagakerjaan calculations
-  **PTKP Support**: All PTKP statuses (TK/0, TK/1, TK/2, TK/3, K/0, K/1, K/2, K/3)
-  **NPWP Surcharge**: Automatic surcharge calculation for non-NPWP holders

## Installation

Install the package via Composer:

```bash
composer require riod94/payroll-calculator-indonesia
```

## Quick Start

### Basic Usage

```php
<?php

require 'vendor/autoload.php';

use PayrollCalculator\PayrollCalculator;
use PayrollCalculator\Constants\CalculationConstants;

// Create employee
$employee = new PayrollCalculator\DataStructures\Employee(
    name: 'John Doe',
    earnings: new PayrollCalculator\DataStructures\Earnings(
        base: 10000000, // Basic salary
        fixedAllowance: 2000000 // Fixed allowance
    ),
    taxable: true,
    hasNPWP: true
);

// Initialize calculator
$calculator = new PayrollCalculator($employee);

// Calculate with GROSS method
$result = $calculator->getCalculation();

// Get results
echo "Take Home Pay: " . number_format($result->takeHomePay, 2) . "\n";
echo "Monthly Gross: " . number_format($result->earnings->monthly->gross, 2) . "\n";
echo "Monthly Nett: " . number_format($result->earnings->monthly->nett, 2) . "\n";
echo "PPh21 Tax: " . number_format($result->taxable->pph->liability->monthly, 2) . "\n";

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

        $annually->gross += $items->base + $items->allowance + $items->premi->JKK + $items->premi->JKM;
        $annually->jkk += $result->company->jkk;
        $annually->jkm += $result->company->jkm;
        $annually->bonus += $calculator->employee->onetime->bonus;
        $annually->THR += $calculator->employee->onetime->holidayAllowance;
        $annually->pph21Paid += $result->taxable->pph->liability->monthly;
    }
}

echo "yearIncomes Tuan A Pada PT.Z\n";
print_r($yearIncomes);
```

## Features

-  **PPh 21 Calculation**: Comprehensive PPh 21 tax calculation for various employee types
-  **BPJS Calculations**: Support for BPJS Kesehatan and BPJS Ketenagakerjaan calculations
-  **Multiple Employee Types**: Support for permanent, non-permanent, commissioners, and other tax subjects
-  **Overtime Calculation**: Built-in overtime payment calculation
-  **Tax Methods**: Support for GROSS, NETT, and GROSS UP calculation methods
-  **Annual & Monthly**: Support for both monthly and annual tax calculations
-  **Resignation Benefits**: Calculate tax for severance pay, compensation pay, and merit pay

## Supported Employee Types

1. **Permanent Employee** (Pegawai Tetap)
2. **Non-Permanent Employee** (Pegawai Tidak Tetap)
3. **Not Employee Sustainable** (Bukan Pegawai berkesinambungan)
4. **Not Employee Sustainable with Other Income** (Bukan Pegawai berkesinambungan dan Memiliki penghasilan lainnya)
5. **Not Employee Unsustainable** (Bukan Pegawai tidak berkesinambungan)
6. **Other Subjects** (Subject pajak lainnya seperti: Peserta Kegiatan, Pensiunan dan Bukan Pegawai)
7. **Board of Commissioners** (Dewan Pengawas / Komisaris)
8. **Foreign Individual Taxpayer** (Wajib Pajak Asing)

## Tax Status (PTKP)

-  **TK/0**: Single, no dependents - Rp 54.000.000
-  **TK/1**: Single, 1 dependent - Rp 58.500.000
-  **TK/2**: Single, 2 dependents - Rp 63.000.000
-  **TK/3**: Single, 3 dependents - Rp 67.500.000
-  **K/0**: Married, no dependents - Rp 58.500.000
-  **K/1**: Married, 1 dependent - Rp 63.000.000
-  **K/2**: Married, 2 dependents - Rp 67.500.000
-  **K/3**: Married, 3 dependents - Rp 72.000.000

## Calculation Methods

### GROSS

Tax is borne by the employee. The tax amount is deducted from the employee's take-home pay.

### NETT

Tax is borne by the company. The employee receives the full gross amount, and the company pays the tax separately.

### GROSS UP

Tax is borne by the company and given to the employee as a tax allowance. The employee receives both the gross amount and the tax amount.

## Configuration

```php
$calculator = new PayrollCalculator();

// Configure company provisions
$calculator->Provisions->company->calculateBPJSKesehatan = true;
$calculator->Provisions->company->calculateOvertime = true;
$calculator->Provisions->company->JKK = true;
$calculator->Provisions->company->JKM = true;
$calculator->Provisions->company->JHT = true;
$calculator->Provisions->company->JIP = true;

// Configure employee
$calculator->Employee->name = "Employee Name";
$calculator->Employee->ptkpStatus = "K/0";
$calculator->Employee->hasNPWP = true;
$calculator->Employee->permanentStatus = true;
$calculator->Employee->earnings->base = 10000000;

// Set calculation method
$calculator->method = PayrollCalculator::GROSS; // or NETT, GROSS_UP
```

## Testing

```bash
composer test
```

## License

ISC License

## Author

Rio Dwi Prabowo - <riyo.s94@gmail.com>

## Repository

https://github.com/riod94/payroll-calculator-indonesia
