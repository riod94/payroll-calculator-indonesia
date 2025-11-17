<?php

declare(strict_types=1);

namespace PayrollCalculator\DataStructures;

use PayrollCalculator\Traits\MapArrayObject;

class Onetime
{
    use MapArrayObject;

    public function __construct(
        public Allowances $allowances = new Allowances(),
        public Deductions $deductions = new Deductions(),
        public Benefits $benefits = new Benefits(),
        public float $bonus = 0,
        public float $holidayAllowance = 0,
    ) {}
}
