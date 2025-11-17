<?php

declare(strict_types=1);

namespace PayrollCalculator\Taxes;

use PayrollCalculator\Constants\PphConstants;
use PayrollCalculator\DataStructures\{TaxableResult, PphResult, PtkpResult, LiabilityResult};

class Pph23 extends AbstractPph
{
    public function calculate(): TaxableResult
    {
        if ($this->calculator->employee->taxable) {
            $this->calculatePph23();
        }

        return $this->result;
    }

    private function calculatePph23(): void
    {
        // PPh23 calculation logic - withholding tax on services, rentals, and other payments
        // Based on 2025 Indonesian tax regulations

        // Initialize PPh23 result structure
        $this->result->pph23 = new PphResult();
        $this->result->pph23->ptkp = new PtkpResult();
        $this->result->pph23->liability = new LiabilityResult();

        // Calculate gross income for PPh23 (services, rentals, consulting, etc.)
        $grossIncome = $this->calculator->result->earnings->base +
            $this->calculator->result->earnings->fixedAllowance +
            $this->calculator->result->allowances->sum() +
            $this->calculator->result->bonus->sum();

        // Add overtime if calculated
        if (isset($this->calculator->result->earnings->overtime) && isset($this->calculator->result->earnings->overtime->payment)) {
            $grossIncome += $this->calculator->result->earnings->overtime->payment;
        }

        // PPh23 rates based on 2025 regulations:
        // - 2% for general services and rentals
        // - 10% for consulting services
        // - 15% for certain specialized services
        // Using 2% as default rate for general PPh23 calculation
        $pph23Rate = 0.02; // 2% standard rate
        // TODO: implement rate calculation based on income type

        // Calculate tax liability
        $taxLiability = $grossIncome * $pph23Rate;

        // Apply NPWP surcharge if applicable (100% for no NPWP for PPh23)
        if (!$this->calculator->employee->hasNPWP) {
            $taxLiability += $taxLiability * 1.0; // 100% surcharge
        }

        $this->result->pph23->liability->amount = round($taxLiability, 2);
    }
}
