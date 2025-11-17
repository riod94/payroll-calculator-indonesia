<?php

declare(strict_types=1);

namespace PayrollCalculator\Taxes;

use PayrollCalculator\Constants\PphConstants;
use PayrollCalculator\DataStructures\TaxableResult;
use PayrollCalculator\PayrollCalculator;

class Pph21LastTaxPeriod extends AbstractPph
{
    public function __construct(PayrollCalculator $calculator)
    {
        parent::__construct($calculator);
        $this->result = new TaxableResult();
    }

    public function calculate(): TaxableResult
    {
        if ($this->calculator->result->earnings->monthly->gross > $this->calculator->provisions->state->pph21EarningsNettLowerLimit && $this->calculator->employee->taxable) {
            // Annual PTKP base on number of dependents family
            $this->result->pph->ptkp->amount = PphConstants::LIST_OF_PTKP[$this->calculator->employee->ptkpStatus] ?? 0;
            $this->result->pph->ptkp->status = $this->calculator->employee->ptkpStatus;

            // Get PKP (Penghasilan Kena Pajak) Setahun
            $currentYearGross = $this->calculator->result->earnings->annualy->gross;
            $previousPaidTax = $this->calculator->employee->lastTaxPeriod->annualPPh21Paid;
            
            $this->result->pph->pkp = $this->roundDownPkp(
                $currentYearGross - $this->result->pph->ptkp->amount
            );

            // Calculate PPh for Last Tax Period
            $annualLiability = $this->getPph($this->result->pph->pkp);
            $this->result->pph->liability->annual = $annualLiability - $previousPaidTax;

            // Surcharge for No Npwp
            if ($this->result->pph->liability->annual > 0) {
                if (!$this->calculator->employee->hasNPWP) {
                    $this->result->pph->liability->annual = $this->result->pph->liability->annual + 
                        ($this->result->pph->liability->annual * ($this->calculator->provisions->state->pph21NoNpwpSurchargeRate / 100));
                }

                $this->result->pph->liability->monthly = $this->result->pph->liability->annual;
            } else {
                $this->result->pph->liability->annual = 0;
                $this->result->pph->liability->monthly = 0;
            }
        } else {
            $this->result->pph->liability->annual = 0;
            $this->result->pph->liability->monthly = 0;
        }

        return $this->result;
    }

    private function roundDownPkp(float $pkp): int
    {
        return (int) floor($pkp / 1000) * 1000;
    }

    private function getPph(float|int $pkp): float
    {
        $yearOfTariffLayer = $this->calculator->provisions->state->yearOfTariffLayer;
        $rateLayers = PphConstants::PPH_RATE_LAYER_LIST[$yearOfTariffLayer] ?? [];

        $tax = 0;

        foreach ($rateLayers as $layer) {
            if ($pkp > $layer['from']) {
                $taxableAmount = min($pkp, $layer['until']) - $layer['from'];
                if ($layer['until'] == 0) { // Last layer (unlimited)
                    $taxableAmount = $pkp - $layer['from'];
                }
                
                $tax += $taxableAmount * $layer['rate_percentage'];
                
                if ($layer['until'] == 0 || $pkp <= $layer['until']) {
                    break;
                }
            }
        }

        return $tax;
    }
}
