# Payroll Calculator Testing Documentation

This directory contains comprehensive unit and integration tests for the Payroll Calculator PHP library.

## Test Structure

```
tests/
├── Unit/
│   ├── Constants/
│   │   └── CalculationConstantsTest.php
│   ├── DataStructures/
│   │   └── EmployeeTest.php
│   ├── Taxes/
│   │   ├── Pph21Test.php
│   │   ├── Pph21NonPermanentEmployeeTest.php
│   │   ├── Pph21NotEmployeeTest.php
│   │   ├── Pph21CommissionerTest.php
│   │   ├── Pph21LastTaxPeriodTest.php
│   │   ├── Pph21OtherSubjectTest.php
│   │   └── Pph21ResignTest.php
│   └── Traits/
│       └── MapArrayObjectTest.php
├── Integration/
│   └── PayrollCalculatorIntegrationTest.php
├── TestCase.php
└── README.md
```

## Running Tests

### Prerequisites

Install dependencies first:
```bash
composer install
```

### Run All Tests

```bash
composer test
```

### Run Tests with Coverage

```bash
composer test-coverage
```

This will generate an HTML coverage report in the `coverage/` directory.

### Run Specific Test Suites

```bash
# Run only unit tests
./vendor/bin/phpunit --testsuite Unit

# Run only integration tests
./vendor/bin/phpunit --testsuite Integration

# Run specific test class
./vendor/bin/phpunit tests/Unit/Taxes/Pph21Test.php

# Run specific test method
./vendor/bin/phpunit tests/Unit/Taxes/Pph21Test.php::testCalculateWithBasicIncome
```

## Test Coverage Areas

### Unit Tests

1. **Tax Classes** - Test individual tax calculation logic:
   - PPh21 for permanent employees
   - PPh21 for non-permanent employees
   - PPh21 for commissioners
   - PPh21 for non-employees
   - PPh21 for other subjects
   - PPh21 for last tax period
   - PPh21 for resignation benefits

2. **Data Structures** - Test object creation and initialization:
   - Employee data structure
   - Default values and custom constructors

3. **Constants** - Test constant values and uniqueness:
   - Calculation constants (NETT, GROSS, GROSS_UP)

4. **Traits** - Test utility functionality:
   - MapArrayObject trait methods

### Integration Tests

1. **End-to-End Payroll Calculation** - Complete payroll scenarios:
   - Monthly payroll with various components
   - Different calculation methods (NETT, GROSS, GROSS_UP)
   - Various employee types and statuses
   - Resignation calculations
   - Edge cases and boundary conditions

## Test Data and Scenarios

### Employee Types Tested

- **Permanent Employees** - Regular full-time employees
- **Non-Permanent Employees** - Contract workers
- **Commissioners** - Board commissioners
- **Non-Employees** - Consultants and service providers
- **Other Subjects** - Pensioners, activity participants

### Tax Status Scenarios

- Different PTKP statuses (TK/0, K/1, etc.)
- With and without NPWP (20% surcharge)
- Taxable and non-taxable employees
- Various income levels (zero, low, medium, high)

### Calculation Methods

- **NETT** - Tax paid by company
- **GROSS** - Tax paid by employee
- **GROSS_UP** - Tax treated as allowance

### Special Cases

- Resignation benefits (severance pay, compensation)
- Last tax period calculations
- Overtime payments
- Holiday allowances (THR)
- Performance bonuses

## Test Utilities

### TestCase Base Class

The `PayrollCalculator\Tests\TestCase` class provides helper methods:

- `createCalculator()` - Creates calculator with default test values
- `createNonPermanentCalculator()` - For contract workers
- `createCommissionerCalculator()` - For commissioners
- `createResignCalculator()` - For resignation calculations
- `initializeResultStructure()` - Sets up result data for tax tests
- `assertReasonableTax()` - Validates tax calculation results
- `assertReasonableTakeHomePay()` - Validates take-home pay calculations

### Test Data

Test scenarios use realistic Indonesian payroll data:
- Base salaries: Rp 2,000,000 - Rp 50,000,000
- Allowances: Rp 2,000,000 - Rp 20,000,000
- Bonuses: Rp 5,000,000 - Rp 20,000,000
- Severance pay: Rp 30,000,000 - Rp 200,000,000

## Contributing Tests

When adding new tests:

1. Follow the existing naming conventions
2. Use the base `TestCase` class for consistency
3. Test both positive and negative scenarios
4. Include edge cases and boundary conditions
5. Add descriptive test method names
6. Use realistic test data

### Example Test Structure

```php
public function testScenarioDescription(): void
{
    // Arrange
    $calculator = $this->createCalculator();
    $calculator->employee->earnings->base = 10000000;
    
    // Act
    $result = $calculator->getCalculation();
    
    // Assert
    $this->assertNotNull($result);
    $this->assertReasonableTax($result->taxable->pph->liability->monthly);
}
```

## Continuous Integration

These tests are designed to run in CI/CD environments:

- Fast execution (under 30 seconds)
- No external dependencies
- Deterministic results
- Clear error messages
- High coverage target (>90%)

## Troubleshooting

### Common Issues

1. **Missing Dependencies** - Run `composer install`
2. **Permission Errors** - Ensure vendor directory is writable
3. **Memory Issues** - Increase PHP memory limit if needed
4. **Timezone Issues** - Set timezone in php.ini

### Debug Mode

Run tests with verbose output:
```bash
./vendor/bin/phpunit --verbose --debug
```

### Coverage Issues

If coverage is incomplete, check:
- All test methods are public
- Proper class and method names
- No syntax errors in test files
- All source files are included in autoload
