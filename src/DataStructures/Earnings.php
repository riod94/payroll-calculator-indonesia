<?php

declare(strict_types=1);

namespace PayrollCalculator\DataStructures;

use PayrollCalculator\Traits\MapArrayObject;

class Earnings
{
    use MapArrayObject;

    public function __construct(
        public float $baseBeforeProrate = 0,
        public float $base = 0,
        public float $fixedAllowance = 0,
        public float $bpjsKesBase = 0,
        public float $bpjsKetenagakerjaanBase = 0,
        public float $onetimeAllowance = 0,
        public float $onetimeDeduction = 0,
        public float $onetimeBenefit = 0,
    ) {}
}
