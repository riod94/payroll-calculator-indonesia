<?php

declare(strict_types=1);

namespace PayrollCalculator\Taxes;

use PayrollCalculator\Constants\PphConstants;
use PayrollCalculator\DataStructures\{TaxableResult, PphResult, PtkpResult, ParseTerDetailType, LiabilityResult};

class Pph26 extends AbstractPph
{
    public function calculate(): TaxableResult
    {
        if ($this->calculator->employee->taxable) {
            $this->calculatePph26();
        }

        return $this->result;
    }

    private function calculatePph26(): void
    {
        // PPh26 calculation logic
        // PPh26 applies to certain types of income like dividends, interest, royalties, etc.
        // For now, implementing basic structure based on the reference

        // Initialize PPh26 result structure
        $this->result->pph26 = new PphResult();
        $this->result->pph26->ptkp = new PtkpResult();
        $this->result->pph26->liability = new LiabilityResult();

        // Calculate gross income (base + fixed allowance + other components)
        $grossIncome = $this->calculator->result->earnings->base +
                      $this->calculator->result->earnings->fixedAllowance +
                      $this->calculator->result->allowances->sum() +
                      $this->calculator->result->bonus->sum();

        // Add overtime if calculated
        if (isset($this->calculator->result->earnings->overtime) && is_numeric($this->calculator->result->earnings->overtime)) {
            $grossIncome += $this->calculator->result->earnings->overtime;
        }

        // PPh26 typically has different rates and calculation than PPh21
        // For now, implementing basic calculation - this might need adjustment
        // based on specific PPh26 tax rules

        // PPh26 final tax rate (typically 20% for most PPh26 income types)
        $pph26Rate = (20 / 100);

        // Calculate tax liability
        $taxLiability = $grossIncome * $pph26Rate;

        // Apply NPWP surcharge if applicable (20% for no NPWP)
        if (!$this->calculator->employee->hasNPWP) {
            $taxLiability += $taxLiability * ($this->calculator->provisions->state->pph21NoNpwpSurchargeRate / 100);
        }

        $this->result->pph26->liability->amount = round($taxLiability, 2);
    }
}
