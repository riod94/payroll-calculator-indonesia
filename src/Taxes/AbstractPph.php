<?php

declare(strict_types=1);

namespace PayrollCalculator\Taxes;

use PayrollCalculator\PayrollCalculator;
use PayrollCalculator\Constants\PphConstants;
use PayrollCalculator\DataStructures\{TaxableResult, PphResult, PtkpResult, ParseTerDetailType, LiabilityResult, PphResignResult, ResignLiabilityResult};

abstract class AbstractPph
{
    public PayrollCalculator $calculator;
    public TaxableResult $result;

    abstract public function calculate(): TaxableResult;

    public function __construct(PayrollCalculator $calculator)
    {
        $this->calculator = $calculator;
        
        $defaultTer = new ParseTerDetailType();
        
        $this->result = new TaxableResult(
            pph: new PphResult(
                ptkp: new PtkpResult(),
                pkp: 0,
                ter: $defaultTer,
                liability: new LiabilityResult()
            ),
            pphBonus: new PphResult(
                ptkp: new PtkpResult(),
                pkp: 0,
                ter: $defaultTer,
                liability: new LiabilityResult()
            ),
            pphResign: new PphResignResult(
                ptkp: new PtkpResult(),
                pkp: 0,
                ter: $defaultTer,
                liability: new ResignLiabilityResult()
            )
        );
    }

    protected function calculatePtkp(): int
    {
        $ptkpStatus = $this->calculator->employee->ptkpStatus;
        return PphConstants::LIST_OF_PTKP[$ptkpStatus] ?? 0;
    }

    protected function calculatePkp(int $netIncome, int $ptkp): int
    {
        $pkp = $netIncome - $ptkp;
        return max(0, $pkp);
    }

    protected function parseTer(int $gross): ParseTerDetailType
    {
        $category = '';
        $rate = 0;
        $from = 0;
        $until = 0;

        // Get PTKP category
        $ptkpStatus = $this->calculator->employee->ptkpStatus;
        $category = PphConstants::LIST_OF_PTKP_CATEGORY[$ptkpStatus] ?? '';

        // Find TER rate based on gross income
        if (isset($this->calculator->provisions->state->terMonthly[$category])) {
            foreach ($this->calculator->provisions->state->terMonthly[$category] as $ter) {
                if ($gross >= $ter['from'] && $gross <= $ter['until']) {
                    $rate = $ter['rate'];
                    $from = $ter['from'];
                    $until = $ter['until'];
                    break;
                }
            }
        }

        return new ParseTerDetailType(
            gross: $gross,
            category: $category,
            from: $from,
            until: $until,
            rate: $rate
        );
    }

    protected function calculatePph21(int $pkp): LiabilityResult
    {
        $yearOfTariffLayer = $this->calculator->provisions->state->yearOfTariffLayer;
        $rateLayers = PphConstants::PPH_RATE_LAYER_LIST[$yearOfTariffLayer] ?? [];

        $monthlyTax = 0;
        $annualTax = 0;
        $monthlyGrossUp = 0;
        $rule = 0;

        foreach ($rateLayers as $layer) {
            if ($pkp > $layer['from']) {
                $taxableAmount = min($pkp, $layer['until']) - $layer['from'];
                if ($layer['until'] == 0) { // Last layer (unlimited)
                    $taxableAmount = $pkp - $layer['from'];
                }
                
                $annualTax += $taxableAmount * $layer['rate_percentage'];
                $rule = $layer['index'];
                
                if ($layer['until'] == 0 || $pkp <= $layer['until']) {
                    break;
                }
            }
        }

        $monthlyTax = $annualTax / 12;
        $monthlyGrossUp = $annualTax / 12;

        return new LiabilityResult(
            rule: $rule,
            monthly: $monthlyTax,
            annual: $annualTax,
            monthlyGrossUp: $monthlyGrossUp
        );
    }

    protected function applyNoNpwpSurcharge(float $tax): float
    {
        if (!$this->calculator->employee->hasNPWP) {
            return $tax * (1 + $this->calculator->provisions->state->pph21NoNpwpSurchargeRate);
        }
        return $tax;
    }

    protected function calculateDailyTer(int $grossDaily): ParseTerDetailType
    {
        $rate = 0;
        $from = 0;
        $until = 0;

        foreach (PphConstants::TER_DAILY as $ter) {
            if ($grossDaily >= $ter['from'] && $grossDaily <= $ter['until']) {
                $rate = $ter['rate'];
                $from = $ter['from'];
                $until = $ter['until'];
                break;
            }
        }

        return new ParseTerDetailType(
            gross: $grossDaily,
            category: 'daily',
            from: $from,
            until: $until,
            rate: $rate
        );
    }
}
