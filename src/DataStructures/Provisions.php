<?php

declare(strict_types=1);

namespace PayrollCalculator\DataStructures;

use PayrollCalculator\Traits\MapArrayObject;

class Provisions
{
    use MapArrayObject;

    public function __construct(
        public CompanyProvisions $company = new CompanyProvisions(),
        public StateProvisions $state = new StateProvisions(),
    ) {}
}

class CompanyProvisions
{
    use MapArrayObject;

    public function __construct(
        public bool $useBpjsKesehatan = false,
        public bool $useBpjsKetenagakerjaan = false,
        public bool $useEffectiveRates = true,
        public bool $calculateOvertime = false,
        public bool $calculateBPJSKesehatan = false,
        public bool $bpjsKesEmpTaxable = false,
        public bool $bpjsTkEmpTaxable = false,
        public bool $jkk = false,
        public bool $jkm = false,
        public bool $jht = false,
        public bool $jip = false,
        public float $jkkRiskGrade = 2,
    ) {}
}

class StateProvisions
{
    use MapArrayObject;

    public function __construct(
        public bool $overtimeRegulationCalculation = false,
        public float $provinceMinimumWage = 3940972,
        public float $bpjsKesLowerLimit = 3940972,
        public float $bpjsKesUpperLimit = 12000000,
        public float $bpjsTkLowerLimit = 3940972,
        public float $bpjsTkUpperLimit = 8939700,
        public float $highestWage = 8939700,
        public float $bpjsKesCompanyRate = 0.04,
        public float $bpjsKesEmployeeRate = 0.01,
        public float $employeeJhtRate = 2,
        public float $companyJhtRate = 3.7,
        public float $employeeJpRate = 1,
        public float $companyJpRate = 2,
        public float $jkmRate = 0.3,
        public float $pph21EarningsNettLowerLimit = 4500000,
        public float $pph21MonthlyPositionTaxLimit = 500000,
        public float $pph21AnnualyPositionTaxLimit = 6000000,
        public float $pph21NoNpwpSurchargeRate = 0.2,
        public int $yearOfTariffLayer = 2022,
        public array $terMonthly = [],
    ) {
        // Initialize terMonthly with effective rate data
        // This will be populated later with actual effective rate data
    }
}
