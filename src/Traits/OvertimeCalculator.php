<?php

declare(strict_types=1);

namespace PayrollCalculator\Traits;

use PayrollCalculator\DataStructures\OvertimeSummary;
use PayrollCalculator\Types\{
    OvertimeCalculationType,
    OvertimeDetailsType,
    OvertimeMultiplierDetailType,
    OvertimeSummaryType
};

trait OvertimeCalculator
{
    private static array $overtime = [
        'earning' => 0,
        'reports' => [],
        'note' => null,
        'adjustment' => 0,
    ];

    /**
     * Perhitungan overtime lama
     */
    public static function overtimeCalculation(
        float $baseEarning,
        array $overtimeDetails,
        OvertimeSummary $summary
    ): OvertimeCalculationType {
        $overtimeAmount = $summary->totalOvertimePayment ?? 0;
        $overtimeReports = [];

        if (!empty($overtimeDetails)) {
            foreach ($overtimeDetails as $overtime) {
                $overtimeRate = $overtime->overtimeRate >= 1
                    ? $overtime->overtimeRate
                    : $baseEarning / 173;

                $overtimePay = $overtimeRate * $overtime->overtimeMultiplier;

                // Update overtime object
                $overtime->overtimeRate = $overtimeRate;
                $overtime->overtimePay = $overtimePay;
                $overtime->totalPay = $overtimePay + $overtime->mealAllowance;

                $overtimeReports[] = self::parseReportOvertime($overtime);

                if ($overtimeAmount == 0) {
                    $overtimeAmount += $overtime->totalPay;
                }
            }
        }

        self::$overtime['note'] = $summary?->note ?? null;
        self::$overtime['adjustment'] = $summary?->overtimeAdjustment ?? 0;
        self::$overtime['earning'] = $overtimeAmount;
        self::$overtime['reports'] = $overtimeReports;

        return new OvertimeCalculationType(
            earning: self::$overtime['earning'],
            reports: self::$overtime['reports'],
            note: self::$overtime['note'],
            adjustment: self::$overtime['adjustment']
        );
    }

    /**
     * Parsing data overtime lama
     */
    private static function parseReportOvertime(OvertimeDetailsType $overtime): OvertimeDetailsType
    {
        if (!$overtime || empty(get_object_vars($overtime))) {
            return $overtime;
        }

        $multiplierDetails = [];
        if (!empty($overtime->overtimeMultiplierDetail)) {
            foreach ($overtime->overtimeMultiplierDetail as $detail) {
                $multiply = $detail['multiply'] ??
                    $detail['multiplier_amount'] ??
                    $detail['multiplier'] ?? 0;

                $multiplierDetails[] = new OvertimeMultiplierDetailType(
                    hours: $detail['hours'] ?? 0,
                    multiplier: $detail['multiplier'] ?? 0,
                    multiply: $multiply
                );
            }
        }

        return new OvertimeDetailsType(
            overtimeType: $overtime->overtimeType,
            compensationType: $overtime->compensationType,
            payMethod: $overtime->payMethod,
            overtimeRate: $overtime->overtimeRate,
            overtimeDate: $overtime->overtimeDate,
            overtimeDuration: $overtime->overtimeDuration,
            overtimeMultiplier: $overtime->overtimeMultiplier,
            overtimePay: $overtime->overtimePay,
            totalPay: $overtime->totalPay,
            mealAllowanceCount: $overtime->mealAllowanceCount,
            mealAllowance: $overtime->mealAllowance,
            overtimeMultiplierDetail: $multiplierDetails
        );
    }
}
