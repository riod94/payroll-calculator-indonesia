<?php

declare(strict_types=1);

namespace PayrollCalculator\DataStructures;

use PayrollCalculator\Traits\MapArrayObject;

class Resign
{
    use MapArrayObject;

    public function __construct(
        public bool $repayment = false,
        public float $compensationPay = 0,
        public float $severancePay = 0,
        public float $meritPay = 0,
        public float $remainingLoan = 0,
        public float $bpjsKes = 0,
        public float $bpjsKesFamily = 0,
        public float $bpjsTK = 0,
    ) {}
}
