<?php

declare(strict_types=1);

namespace PayrollCalculator\DataStructures;

use PayrollCalculator\Traits\MapArrayObject;
use PayrollCalculator\Types\OvertimeDetailsType;

class Employee
{
    use MapArrayObject;

    public function __construct(
        public string $name = '',
        public bool $taxable = true,
        public bool $permanentStatus = true,
        public int $yearPeriod = 0,
        public int $monthPeriod = 0,
        public string $ptkpStatus = 'TK/0',
        public bool $hasNPWP = false,
        public bool $maritalStatus = false,
        public int $numberOfDependentsFamily = 0,
        public int $jhtPayor = 3,
        public int $bpjsKesPayor = 3,
        public int $jpPayor = 3,
        public int $monthMultiplier = 12,
        public int $rateMultiplier = 1,
        public bool $isNewlyJoinedEmployee = false,
        public bool $isResignedEmployee = false,
        public ?Earnings $earnings = null,
        public ?Components $components = null,
        public ?Onetime $onetime = null,
        public ?Overtimes $overtimes = null,
        public ?Resign $resign = null,
        public ?LastTaxPeriod $lastTaxPeriod = null,
    ) {
        $this->yearPeriod = $yearPeriod ?: (int)date('Y');
        $this->monthPeriod = $monthPeriod ?: (int)date('n');
        $this->earnings = $earnings ?: new Earnings();
        $this->components = $components ?: new Components();
        $this->onetime = $onetime ?: new Onetime();
        $this->overtimes = $overtimes ?: new Overtimes();
        $this->resign = $resign ?: new Resign();
        $this->lastTaxPeriod = $lastTaxPeriod ?: new LastTaxPeriod();
    }
}
