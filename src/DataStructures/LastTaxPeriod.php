<?php

declare(strict_types=1);

namespace PayrollCalculator\DataStructures;

use PayrollCalculator\Traits\MapArrayObject;

class LastTaxPeriod
{
    use MapArrayObject;

    public function __construct(
        public float $annualGross = 0,
        public float $annualJIPEmployee = 0,
        public float $annualJHTEmployee = 0,
        public float $annualBonus = 0,
        public float $annualHolidayAllowance = 0,
        public float $annualOnetime = 0,
        public float $annualPPh21Paid = 0,
        public float $annualZakat = 0,
        public float $annualOtherDonation = 0,
    ) {}
}
