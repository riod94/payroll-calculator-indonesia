<?php

declare(strict_types=1);

namespace PayrollCalculator\DataStructures;

use PayrollCalculator\Traits\MapArrayObject;

class Company
{
    use MapArrayObject;

    public function __construct(
        public float $bpjsKesehatan = 0,
        public float $jkk = 0,
        public float $jkm = 0,
        public float $jht = 0,
        public float $jip = 0,
    ) {}
}
