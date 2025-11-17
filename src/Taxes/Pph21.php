<?php

declare(strict_types=1);

namespace PayrollCalculator\Taxes;

use PayrollCalculator\Constants\PphConstants;
use PayrollCalculator\DataStructures\{TaxableResult, PphResult, PtkpResult, ParseTerDetailType, LiabilityResult};

class Pph21 extends AbstractPph
{
    public function calculate(): TaxableResult
    {
        if ($this->calculator->provisions->company->useEffectiveRates) {
            $this->calculateTER();
        } else {
            $this->calculatePph();
            $this->calculatePphBonus();
        }

        return $this->result;
    }

    private function calculatePph(): void
    {
        /**
         * PPh21 dikenakan bagi yang memiliki penghasilan lebih dari 4500000
         */
        if ($this->calculator->result->earnings->monthly->nett > $this->calculator->provisions->state->pph21EarningsNettLowerLimit && $this->calculator->employee->taxable) {
            // Annual PTKP base on number of dependents family
            $this->result->pph->ptkp->amount = PphConstants::LIST_OF_PTKP[$this->calculator->employee->ptkpStatus] ?? 0;
            $this->result->pph->ptkp->status = $this->calculator->employee->ptkpStatus;

            // Get Earnings Tax
            $earningTax = ($this->calculator->result->earnings->annualy->nett - $this->result->pph->ptkp->amount) * ($this->getRate($this->calculator->result->earnings->monthly->nett) / 100);

            // Get PKP (Penghasilan Kena Pajak) Setahun
            $this->result->pph->pkp = $this->roundDownPkp(
                $this->calculator->result->earnings->annualy->nett + 
                $this->calculator->employee->onetime->holidayAllowance + 
                $this->calculator->employee->onetime->bonus
            ) - $this->result->pph->ptkp->amount;

            $this->result->pph->liability->annual = $this->result->pph->pkp - $earningTax;

            // Gross Or Gross Up Calculation
            if ($this->calculator->method === 'GROSSUP') {
                $annualLiability = $this->grossUpPph($this->result->pph->pkp);
                $this->result->pph->liability->annual = $annualLiability;
                $this->result->pph->pkp = $this->roundDownPkp(
                    $this->result->pph->pkp + ($this->result->pph->liability->annual / $this->calculator->employee->monthMultiplier)
                );
            } else {
                $annualLiability = $this->getPph($this->result->pph->pkp);
                $this->result->pph->liability->annual = $annualLiability;
            }

            // Surcharge for No Npwp
            if ($this->result->pph->liability->annual > 0) {
                if (!$this->calculator->employee->hasNPWP) {
                    $this->result->pph->liability->annual = $this->result->pph->liability->annual + 
                        ($this->result->pph->liability->annual * ($this->calculator->provisions->state->pph21NoNpwpSurchargeRate / 100));
                }

                $this->result->pph->liability->monthly = $this->result->pph->liability->annual / $this->calculator->employee->monthMultiplier;
            } else {
                $this->result->pph->liability->annual = 0;
                $this->result->pph->liability->monthly = 0;
            }
        } else {
            $this->result->pph->liability->annual = 0;
            $this->result->pph->liability->monthly = 0;
        }
    }

    private function calculatePphBonus(): void
    {
        $this->calculator->employee->earnings->onetimeAllowance = 
            $this->calculator->employee->onetime->allowances->sum() + 
            $this->calculator->employee->onetime->bonus + 
            $this->calculator->employee->onetime->holidayAllowance;
            
        if ($this->calculator->employee->earnings->onetimeAllowance > $this->calculator->provisions->state->pph21EarningsNettLowerLimit && $this->calculator->employee->taxable) {
            // Annual PTKP base on number of dependents family
            $this->result->pphBonus->ptkp->amount = PphConstants::LIST_OF_PTKP[$this->calculator->employee->ptkpStatus] ?? 0;
            $this->result->pphBonus->ptkp->status = $this->calculator->employee->ptkpStatus;

            // Get PKP Setahun
            $this->result->pphBonus->pkp = $this->roundDownPkp(
                ($this->calculator->employee->earnings->onetimeAllowance * $this->calculator->employee->monthMultiplier) - 
                $this->result->pphBonus->ptkp->amount
            );

            // Gross Or Gross Up Calculation
            if ($this->calculator->method === 'GROSSUP') {
                $annualLiability = $this->grossUpPph($this->result->pphBonus->pkp);
                $this->result->pphBonus->liability->annual = $annualLiability;
                $this->result->pphBonus->pkp = $this->roundDownPkp(
                    $this->result->pphBonus->pkp + ($this->result->pphBonus->liability->annual / $this->calculator->employee->monthMultiplier)
                );
            } else {
                $annualLiability = $this->getPph($this->result->pphBonus->pkp);
                $this->result->pphBonus->liability->annual = $annualLiability;
            }

            if ($this->result->pphBonus->liability->annual > 0) {
                if (!$this->calculator->employee->hasNPWP) {
                    $this->result->pphBonus->liability->annual = $this->result->pphBonus->liability->annual + 
                        ($this->result->pphBonus->liability->annual * ($this->calculator->provisions->state->pph21NoNpwpSurchargeRate / 100));
                }

                $this->result->pphBonus->liability->monthly = $this->result->pphBonus->liability->annual / $this->calculator->employee->monthMultiplier;
            } else {
                $this->result->pphBonus->liability->annual = 0;
                $this->result->pphBonus->liability->monthly = 0;
            }
        } else {
            $this->result->pphBonus->liability->annual = 0;
            $this->result->pphBonus->liability->monthly = 0;
        }
    }

    private function calculateTER(): void
    {
        // Implementasi perhitungan Pph 21 Berdasarkan Tarif Efektif Rata-Rata sesuai peraturan PP No 58 Tahun 2023
        // TODO: Implement TER calculation
        $this->calculatePph();
        $this->calculatePphBonus();
    }

    private function getRate(float|int $gross): float
    {
        $yearOfTariffLayer = $this->calculator->provisions->state->yearOfTariffLayer;
        $rateLayers = PphConstants::PPH_RATE_LAYER_LIST[$yearOfTariffLayer] ?? [];

        foreach ($rateLayers as $layer) {
            if ($gross >= $layer['from'] && ($gross <= $layer['until'] || $layer['until'] == 0)) {
                return $layer['rate_percentage'] * 100;
            }
        }

        return 0;
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

    private function grossUpPph(float|int $pkp): float
    {
        // Gross up calculation formula
        // This is a simplified version - actual implementation may be more complex
        $tax = $this->getPph($pkp);
        return $tax * 1.2; // Simplified gross up calculation
    }
}
