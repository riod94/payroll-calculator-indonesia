<?php

declare(strict_types=1);

namespace PayrollCalculator\DataStructures;

use PayrollCalculator\Traits\MapArrayObject;

class Overtimes
{
    use MapArrayObject;

    public function __construct(
        public array $details = [],
        public OvertimeSummary $summary = new OvertimeSummary(),
    ) {}
}

class OvertimeSummary
{
    use MapArrayObject;

    public function __construct(
        public int $duration = 0,
        public int $dutationHour = 0,
        public float $overtimePay = 0,
        public float $overtimeAdjustment = 0,
        public float $totalOvertimePayment = 0,
        public ?string $note = null,
    ) {}
}
