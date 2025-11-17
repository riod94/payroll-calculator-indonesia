<?php

declare(strict_types=1);

namespace PayrollCalculator\Taxes;

use PayrollCalculator\Constants\PphConstants;
use PayrollCalculator\DataStructures\{TaxableResult, PphResignResult};
use PayrollCalculator\PayrollCalculator;

class Pph21Resign extends AbstractPph
{
    public function __construct(PayrollCalculator $calculator)
    {
        parent::__construct($calculator);
        $this->result = new TaxableResult();
    }

    public function calculate(): TaxableResult
    {
        // Calculate total resign income from all components
        $totalResignIncome = $this->calculator->employee->resign->severancePay +
                           $this->calculator->employee->resign->compensationPay +
                           $this->calculator->employee->resign->meritPay;

        if ($totalResignIncome > 0 && $this->calculator->employee->taxable) {
            // Annual PTKP base on number of dependents family
            $this->result->pphResign->ptkp->amount = PphConstants::LIST_OF_PTKP[$this->calculator->employee->ptkpStatus] ?? 0;
            $this->result->pphResign->ptkp->status = $this->calculator->employee->ptkpStatus;

            // Get PKP (Penghasilan Kena Pajak) untuk pesangon
            $resignIncome = $totalResignIncome;
            $this->result->pphResign->pkp = $this->roundDownPkp($resignIncome - $this->result->pphResign->ptkp->amount);

            // Calculate PPh for Resign
            $taxLiability = $this->getPph($this->result->pphResign->pkp);
            $this->result->pphResign->liability->amount = $taxLiability;

            // Surcharge for No Npwp
            if ($this->result->pphResign->liability->amount > 0) {
                if (!$this->calculator->employee->hasNPWP) {
                    $this->result->pphResign->liability->amount = $this->result->pphResign->liability->amount +
                        ($this->result->pphResign->liability->amount * ($this->calculator->provisions->state->pph21NoNpwpSurchargeRate / 100));
                }
            } else {
                $this->result->pphResign->liability->amount = 0;
            }
        } else {
            $this->result->pphResign->liability->amount = 0;
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
