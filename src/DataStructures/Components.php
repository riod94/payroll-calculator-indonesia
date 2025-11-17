<?php

declare(strict_types=1);

namespace PayrollCalculator\DataStructures;

use PayrollCalculator\Traits\MapArrayObject;

class Components
{
    use MapArrayObject;

    public function __construct(
        public Allowances $allowances = new Allowances(),
        public Deductions $deductions = new Deductions(),
        public Benefits $benefits = new Benefits(),
    ) {}
}

class Allowances
{
    use MapArrayObject;
}

class Deductions
{
    use MapArrayObject;
}

class Benefits
{
    use MapArrayObject;
}
