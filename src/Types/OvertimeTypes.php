<?php

declare(strict_types=1);

namespace PayrollCalculator\Types;

class OvertimeDetailsType
{
    public function __construct(
        public readonly string $overtimeType,
        public readonly string $compensationType,
        public readonly string $payMethod,
        public readonly float $overtimeRate,
        public readonly \DateTime $overtimeDate,
        public readonly int $overtimeDuration,
        public readonly float $overtimeMultiplier,
        public readonly float $overtimePay,
        public readonly float $totalPay,
        public readonly int $mealAllowanceCount,
        public readonly float $mealAllowance,
        public readonly array $overtimeMultiplierDetail,
    ) {}
}

class OvertimeMultiplierDetailType
{
    public function __construct(
        public readonly int $hours,
        public readonly float $multiplier,
        public readonly float $multiply,
    ) {}
}

class OvertimeSummaryType
{
    public function __construct(
        public readonly int $duration,
        public readonly int $dutationHour,
        public readonly float $overtimePay,
        public readonly float $overtimeAdjustment,
        public readonly float $totalOvertimePayment,
        public readonly ?string $note,
    ) {}
}

class OvertimeCalculationType
{
    public function __construct(
        public readonly float $earning,
        public readonly array $reports,
        public readonly ?string $note,
        public readonly ?float $adjustment,
    ) {}
}
