<?php

declare(strict_types=1);

namespace PayrollCalculator;

use PayrollCalculator\Constants\{CalculationConstants, PphConstants};
use PayrollCalculator\Types\TaxStatus;
use PayrollCalculator\Traits\{MapArrayObject, OvertimeCalculator};
use PayrollCalculator\DataStructures\{Company, Employee, Provisions, Result, OvertimeResult};
use PayrollCalculator\Taxes\{Pph21, Pph21Commissioner, Pph21LastTaxPeriod, Pph21NonPermanentEmployee, Pph21NotEmployee, Pph21OtherSubject, Pph21Resign, Pph23, Pph26};

class PayrollCalculator
{
    use MapArrayObject;

    public int $taxNumber = 21;
    public TaxStatus $taxStatus = TaxStatus::PERMANENT_EMPLOYEE;
    public string $method = CalculationConstants::GROSS;
    public string $frequency = 'monthly';

    public Provisions $provisions;
    public Employee $employee;
    public Company $company;
    public Result $result;

    public function __construct()
    {
        $this->provisions = new Provisions();
        $this->employee = new Employee();
        $this->company = new Company();
        $this->result = new Result();
    }

    public function getCalculation(): Result
    {
        if ($this->taxNumber == 23) {
            return $this->calculateBaseOnPph23();
        } elseif ($this->taxNumber == 26) {
            return $this->calculateBaseOnPph26();
        } else {
            return $this->calculateBaseOnPph21();
        }
    }

    private function calculateBaseOnPph21(): Result
    {
        $this->result->earnings->base = $this->employee->earnings->base;

        // Sum Employee Components for Taxable
        $allowances = $this->employee->components->allowances->sum();
        $deductions = $this->employee->components->deductions->sum();

        $this->result->earnings->monthly->gross = $this->result->earnings->base + $allowances - $deductions;

        // Calculate overtimes
        if ($this->provisions->company->calculateOvertime) {
            $overtimeCalculation = OvertimeCalculator::overtimeCalculation(
                $this->employee->earnings->base,
                $this->employee->overtimes->details,
                $this->employee->overtimes->summary
            );

            $this->result->earnings->overtime->payment = $overtimeCalculation->earning;
            $this->result->earnings->overtime->adjustment = $overtimeCalculation->adjustment ?? 0;
            $this->result->earnings->overtime->note = $overtimeCalculation->note ?? null;
            $this->result->earnings->overtime->reports = $overtimeCalculation->reports;

            // Lembur ditambahkan sebagai pendapatan bruto bulanan
            $this->result->earnings->monthly->gross += $this->result->earnings->overtime->payment;
        }

        if ($this->employee->permanentStatus) {
            $this->calculatePermanentEmployeeCalculations();
        } else {
            $this->calculateNonPermanentEmployeeCalculations();
        }

        $this->applyCalculationMethod();

        return $this->result;
    }

    private function calculatePermanentEmployeeCalculations(): void
    {
        // Cek Apakah Include hitung BPJS Kesehatan
        if ($this->provisions->company->calculateBPJSKesehatan && $this->employee->earnings->bpjsKesBase >= $this->provisions->state->bpjsKesLowerLimit) {
            $this->calculateBPJSKesehatan();
        }

        // Cek Apakah Include hitung BPJS TK
        $bpjsTkBase = $this->employee->earnings->bpjsKetenagakerjaanBase ?? 0;
        if ($this->provisions->company->jkk && $bpjsTkBase >= $this->provisions->state->bpjsTkLowerLimit) {
            $jkkRiskGrade = PphConstants::LIST_OF_JKK_RISK_GRADE_PERCENT[$this->provisions->company->jkkRiskGrade] ?? $this->provisions->company->jkkRiskGrade;
            $this->company->jkk = (int) round($bpjsTkBase * ($jkkRiskGrade / 100));
        }

        // JKM tidak ada batas atas
        if ($this->provisions->company->jkm && $bpjsTkBase >= $this->provisions->state->bpjsTkLowerLimit) {
            $this->company->jkm = (int) round($this->employee->earnings->bpjsKetenagakerjaanBase * ($this->provisions->state->jkmRate / 100));
        }

        // Set nilai pembanding untuk perhitungan JHT dan JIP
        $this->calculateJHTAndJIP($bpjsTkBase);

        // Add Employee Deductions
        $this->addEmployeeDeductions();

        // Jika settingan BPJS TK merupakan taxable, maka ditambahkan JKK dan JKM ke bruto
        $companyJKK = 0;
        $companyJKM = 0;
        if ($this->provisions->company->bpjsTkEmpTaxable) {
            $companyJKK = $this->company->jkk;
            $companyJKM = $this->company->jkm;
        }

        // Add Company Benefit (JKK & JKM company allowances) to bruto
        $this->result->earnings->monthly->gross += ($companyJKK + $companyJKM);

        // Add BPJS Kesehatan to bruto if BPJS Kes Payor = 2 or 3
        if (in_array($this->employee->bpjsKesPayor, [2, 3])) {
            $this->result->earnings->monthly->gross += $this->company->bpjsKesehatan;
        }

        // Berapa potongan yang diberikan ke bruto
        $this->result->earnings->monthly->gross -= $this->employee->components->deductions->sum();

        $this->calculatePositionTax();
        $this->calculateIrregulars();
        $this->calculateNettIncome();
        $this->calculatePph21();
    }

    private function calculateBPJSKesehatan(): void
    {
        if ($this->employee->earnings->bpjsKesBase < $this->provisions->state->bpjsKesUpperLimit) {
            $this->company->bpjsKesehatan = (int) round($this->employee->earnings->bpjsKesBase * ($this->provisions->state->bpjsKesCompanyRate / 100));
            $this->employee->components->deductions->set('BPJSKesehatan', (int) round($this->employee->earnings->bpjsKesBase * ($this->provisions->state->bpjsKesEmployeeRate / 100)));
        } else {
            $this->company->bpjsKesehatan = (int) round($this->provisions->state->bpjsKesUpperLimit * ($this->provisions->state->bpjsKesCompanyRate / 100));
            $this->employee->components->deductions->set('BPJSKesehatan', (int) round($this->provisions->state->bpjsKesUpperLimit * ($this->provisions->state->bpjsKesEmployeeRate / 100)));
        }

        // Maximum number of dependents family is 5
        if ($this->employee->numberOfDependentsFamily > 5) {
            $bpjsKesAmount = $this->employee->components->deductions->get('BPJSKesehatan') ?? 0;
            $this->employee->components->deductions->set('BPJSKesehatanFamily', (int) round($bpjsKesAmount * ($this->employee->numberOfDependentsFamily - 5)));
        }

        // bpjs kes ditanggung oleh company
        if (in_array($this->employee->bpjsKesPayor, [2, 4])) {
            $bpjsKesAmount = $this->employee->components->deductions->get('BPJSKesehatan') ?? 0;
            $this->company->bpjsKesehatan += (int) round($bpjsKesAmount);
            $this->employee->components->deductions->set('BPJSKesehatan', 0);
        }
    }

    private function calculateJHTAndJIP(float $bpjsTkBase): void
    {
        // Set nilai pembanding untuk perhitungan JHT dan JIP
        if ($this->provisions->company->jht && $bpjsTkBase >= $this->provisions->state->bpjsTkLowerLimit) {
            if ($bpjsTkBase < $this->provisions->state->provinceMinimumWage) {
                $this->company->jht = (int) round($this->employee->earnings->bpjsKetenagakerjaanBase * (($this->provisions->state->companyJhtRate + $this->provisions->state->employeeJhtRate) / 100));
            } else {
                $this->company->jht = (int) round($this->employee->earnings->bpjsKetenagakerjaanBase * ($this->provisions->state->companyJhtRate / 100));
                $this->employee->components->deductions->set('JHT', (int) round($this->employee->earnings->bpjsKetenagakerjaanBase * ($this->provisions->state->employeeJhtRate / 100)));
                if (in_array($this->employee->jhtPayor, [2, 4])) {
                    $jhtAmount = $this->employee->components->deductions->get('JHT') ?? 0;
                    $this->company->jht += (int) round($jhtAmount);
                    $this->employee->components->deductions->set('JHT', 0);
                }
            }
        }

        if ($this->provisions->company->jip && $bpjsTkBase >= $this->provisions->state->bpjsTkLowerLimit) {
            if ($bpjsTkBase < $this->provisions->state->provinceMinimumWage) {
                if ($this->employee->earnings->bpjsKetenagakerjaanBase < $this->provisions->state->bpjsTkUpperLimit) {
                    $this->company->jip = (int) round($this->employee->earnings->bpjsKetenagakerjaanBase * (($this->provisions->state->companyJpRate + $this->provisions->state->employeeJpRate) / 100));
                } else {
                    $this->company->jip = (int) round($this->provisions->state->bpjsTkUpperLimit * (($this->provisions->state->companyJpRate + $this->provisions->state->employeeJpRate) / 100));
                }
            } else {
                if ($this->employee->earnings->bpjsKetenagakerjaanBase < $this->provisions->state->bpjsTkUpperLimit) {
                    $this->company->jip = (int) round($this->employee->earnings->bpjsKetenagakerjaanBase * ($this->provisions->state->companyJpRate / 100));
                    $this->employee->components->deductions->set('JIP', (int) round($this->employee->earnings->bpjsKetenagakerjaanBase * ($this->provisions->state->employeeJpRate / 100)));
                } else {
                    $this->company->jip = (int) round($this->provisions->state->bpjsTkUpperLimit * ($this->provisions->state->companyJpRate / 100));
                    $this->employee->components->deductions->set('JIP', (int) round($this->provisions->state->bpjsTkUpperLimit * ($this->provisions->state->employeeJpRate / 100)));
                }
                if (in_array($this->employee->jpPayor, [2, 4])) {
                    $jipAmount = $this->employee->components->deductions->get('JIP') ?? 0;
                    $this->company->jip += (int) round($jipAmount);
                    $this->employee->components->deductions->set('JIP', 0);
                }
            }
        }
    }

    private function addEmployeeDeductions(): void
    {
        $deductionExceptions = ['BPJSKesehatan', 'JHT', 'JIP', 'BPJSKesehatanFamily'];

        foreach (get_object_vars($this->employee->components->deductions) as $key => $value) {
            if (!in_array($key, $deductionExceptions)) {
                // Process deduction logic here
            }
        }
    }

    private function calculatePositionTax(): void
    {
        $this->result->earnings->annualy->gross = $this->result->earnings->monthly->gross * $this->employee->monthMultiplier;
        $this->result->earnings->annualy->positionTax = $this->result->earnings->annualy->gross * (5 / 100);

        if ($this->result->earnings->monthly->gross > $this->provisions->state->provinceMinimumWage) {
            /**
             * According to Undang-Undang Direktur Jenderal Pajak Nomor PER-32/PJ/2015 Pasal 21 ayat 3
             * Position Deduction is 5% from Annual Gross Income
             */
            $this->result->earnings->monthly->positionTax = $this->result->earnings->monthly->gross * (5 / 100);

            /**
             * Maximum Position Deduction in Indonesia is 500000 / month
             * or 6000000 / year
             */
            if (!$this->employee->isNewlyJoinedEmployee && $this->result->earnings->monthly->positionTax >= $this->provisions->state->pph21MonthlyPositionTaxLimit) {
                $this->result->earnings->monthly->positionTax = $this->provisions->state->pph21MonthlyPositionTaxLimit;
            }

            if ($this->employee->isNewlyJoinedEmployee && $this->result->earnings->annualy->positionTax > $this->provisions->state->pph21AnnualyPositionTaxLimit) {
                $this->result->earnings->monthly->positionTax = $this->provisions->state->pph21MonthlyPositionTaxLimit;
            }
        }
    }

    private function calculateIrregulars(): void
    {
        // Set Irregulars to bruto except for December Period
        $irregulars = (
            $this->employee->onetime->bonus +
            $this->employee->onetime->holidayAllowance +
            $this->employee->onetime->allowances->sum() +
            $this->employee->onetime->deductions->sum() +
            $this->employee->onetime->benefits->sum()
        );

        // Set Irregulars to bruto
        if ($this->employee->monthPeriod == 12) {
            $this->result->bonus->monthly->gross = $this->result->earnings->monthly->gross + $irregulars;
        } else {
            $this->result->earnings->monthly->gross += $irregulars;
            $this->result->bonus->monthly->gross = $this->result->earnings->monthly->gross;
        }

        // Set Employee Gross Bonus
        $this->result->bonus->annualy->gross = $this->result->bonus->monthly->gross * $this->employee->monthMultiplier;
        $this->result->bonus->monthly->positionTax = 0;

        if ($this->result->bonus->monthly->gross > $this->provisions->state->provinceMinimumWage) {
            $positionTax = $this->result->bonus->monthly->gross * (5 / 100);
            if (!$this->employee->isNewlyJoinedEmployee && $positionTax >= $this->provisions->state->pph21MonthlyPositionTaxLimit) {
                $positionTax = $this->provisions->state->pph21MonthlyPositionTaxLimit;
            }
            $this->result->bonus->monthly->positionTax = $positionTax;

            $annualPositionCostBonus = $positionTax * $this->employee->monthMultiplier;
            if ($this->employee->isNewlyJoinedEmployee && 6000000 < $annualPositionCostBonus) {
                $this->result->bonus->monthly->positionTax = $this->provisions->state->pph21MonthlyPositionTaxLimit;
            }
        }
    }

    private function calculateNettIncome(): void
    {
        // Set Monthly Nett
        $this->result->earnings->monthly->nett = $this->result->earnings->monthly->gross - $this->result->earnings->monthly->positionTax;

        $bpjsKesehatanYearly = 0;
        if ($this->provisions->company->bpjsKesEmpTaxable) {
            $bpjsKesAmount = $this->employee->components->deductions->get('BPJSKesehatan') ?? 0;
            $this->result->earnings->monthly->nett -= $bpjsKesAmount;
            $bpjsKesehatanYearly = $bpjsKesAmount * $this->employee->monthMultiplier;
        }

        // Set Annual Nett
        $yearlyPositionCostBonus = $this->result->bonus->monthly->positionTax * $this->employee->monthMultiplier;
        $this->result->bonus->annualy->nett = (int) floor($this->result->bonus->annualy->gross - $yearlyPositionCostBonus - $bpjsKesehatanYearly);

        // Jika jhtPayor tipe [2,3] maka menjadi pengurang (karena taxable)
        if ($this->provisions->company->bpjsTkEmpTaxable && in_array($this->employee->jhtPayor, [2, 3])) {
            $jhtAmount = $this->employee->components->deductions->get('JHT') ?? 0;
            $this->result->earnings->monthly->nett -= $jhtAmount;
            $this->result->bonus->annualy->nett -= ($jhtAmount * $this->employee->monthMultiplier);
        }

        // jika jpPayor tipe [2,3] maka menjadi pengurang (karena taxable)
        if ($this->provisions->company->bpjsTkEmpTaxable && in_array($this->employee->jpPayor, [2, 3])) {
            $jipAmount = $this->employee->components->deductions->get('JIP') ?? 0;
            $this->result->earnings->monthly->nett -= $jipAmount;
            $this->result->bonus->annualy->nett -= ($jipAmount * $this->employee->monthMultiplier);
        }

        // Set Bonus Monthly Nett
        $this->result->bonus->monthly->nett = $this->result->bonus->annualy->nett / $this->employee->monthMultiplier;
    }

    private function calculatePph21(): void
    {
        switch ($this->taxStatus->value) {
            case 2:
                // Pegawai Tidak Tetap
                $this->result->taxable = (new Pph21NonPermanentEmployee($this))->calculate();
                break;

            case 3:
            case 4:
            case 5:
                // Bukan Pegawai
                $this->result->taxable = (new Pph21NotEmployee($this))->calculate();
                break;

            case 6:
                // Peserta Kegiatan / Pensiunan
                $this->result->taxable = (new Pph21OtherSubject($this))->calculate();
                break;

            case 7:
                // Komisaris
                $this->result->taxable = (new Pph21Commissioner($this))->calculate();
                break;

            default:
                // Pegawai Tetap
                if ($this->employee->monthPeriod == 12 || $this->employee->isResignedEmployee) {
                    // PPH 21 December / Masa Pajak terakhir
                    $this->result->taxable = (new Pph21LastTaxPeriod($this))->calculate();
                } else {
                    // PPH 21 Monthly
                    $this->result->taxable = (new Pph21($this))->calculate();
                }

                // PPH 21 Resign terpisah dari pph21 monthly
                if ($this->employee->isResignedEmployee) {
                    $this->result->resign->allowances->compensationPay = $this->employee->resign->compensationPay;
                    $this->result->resign->allowances->severancePay = $this->employee->resign->severancePay;
                    $this->result->resign->allowances->meritPay = $this->employee->resign->meritPay;
                    $this->result->resign->amount = $this->employee->resign->compensationPay + $this->employee->resign->severancePay + $this->employee->resign->meritPay;

                    // Calculate PPh21 for resign
                    $resignTaxResult = (new Pph21Resign($this))->calculate();
                    $this->result->taxable->pphResign = $resignTaxResult->pphResign;
                }
                break;
        }

        // Tunjangan Jabatan selain pegawai tetap dan pegawai tidak tetap
        if ($this->taxStatus->value > 2) {
            $this->result->earnings->monthly->positionTax = 0;
            $this->result->earnings->annualy->positionTax = 0;
        }

        // Set Additional Result
        $this->result->set('allowances', $this->employee->components->allowances);
        $this->result->set('deductions', $this->employee->components->deductions);
        $this->result->set('onetime', $this->employee->onetime);
        $this->result->set('company', $this->company);

        // Set Provisions result
        $this->result->set('BPJSKesEmpTaxable', (bool) $this->provisions->company->bpjsKesEmpTaxable);
        $this->result->set('BPJSTKEmpTaxable', (bool) $this->provisions->company->bpjsTkEmpTaxable);
    }

    private function calculateNonPermanentEmployeeCalculations(): void
    {
        $this->company->bpjsKesehatan = 0;
        $this->employee->components->deductions->set('BPJSKesehatan', 0);

        $this->employee->components->allowances->set('jkk', 0);
        $this->employee->components->allowances->set('jkm', 0);

        $this->employee->components->allowances->set('jht', 0);
        $this->employee->components->deductions->set('JHT', 0);

        $this->employee->components->allowances->set('jip', 0);
        $this->employee->components->deductions->set('JIP', 0);

        // Set result allowances, bonus, deductions
        $this->result->set('allowances', $this->employee->components->allowances);
        $this->result->set('deductions', $this->employee->components->deductions);

        // Pendapatan bersih
        $this->result->earnings->monthly->nett = $this->result->earnings->monthly->gross + $this->result->allowances->sum() - $this->result->deductions->sum();
        $this->result->earnings->annualy->nett = $this->result->earnings->monthly->nett * $this->employee->monthMultiplier;

        $this->result->bonus->monthly->gross = $this->employee->onetime->bonus + $this->employee->onetime->holidayAllowance + $this->employee->onetime->allowances->sum() + $this->employee->onetime->deductions->sum() + $this->employee->onetime->benefits->sum();
        $this->result->bonus->monthly->nett = $this->result->bonus->monthly->gross;
        $this->result->bonus->annualy->gross = $this->result->bonus->monthly->gross * $this->employee->monthMultiplier;
        $this->result->bonus->annualy->nett = $this->result->bonus->annualy->gross;

        if ($this->taxStatus->value == 7) {
            $this->result->taxable = (new Pph21Commissioner($this))->calculate();
        } else {
            $this->result->taxable = (new Pph21($this))->calculate();
        }

        // Initialize penalty if not exists
        if ($this->employee->components->deductions->get('penalty') === null) {
            $this->employee->components->deductions->set('penalty', ['late' => 0, 'absent' => 0]);
        }

        $penalty = $this->employee->components->deductions->get('penalty') ?? ['late' => 0, 'absent' => 0];
        $this->result->takeHomePay = $this->result->earnings->monthly->nett - ($penalty['late'] ?? 0) + ($penalty['absent'] ?? 0);
        $this->result->earnings->monthly->positionTax = 0;
        $this->result->allowances->set('pph21Tax', 0);
        $this->result->takeHomePay = 0;
    }

    private function applyCalculationMethod(): Result
    {
        switch ($this->method) {
            // Pajak ditanggung oleh perusahaan
            case CalculationConstants::NETT:
                $this->result->takeHomePay = $this->result->earnings->base + $this->result->earnings->fixedAllowance + $this->result->resign->amount + $this->result->allowances->sum() - ($this->result->deductions->sum());
                $this->company->set('positionTax', $this->result->earnings->monthly->positionTax);
                $this->company->set('pph21Tax', $this->result->taxable->pph->liability->monthly);
                $this->company->set('pph21Resign', $this->result->taxable->pphResign->liability->amount);
                break;
            // Pajak ditanggung oleh karyawan
            case CalculationConstants::GROSS:
                $this->result->takeHomePay = $this->result->earnings->base + $this->result->earnings->fixedAllowance + $this->result->resign->amount + $this->result->allowances->sum() - ($this->result->deductions->sum() + $this->result->taxable->pph->liability->monthly) - $this->result->taxable->pphResign->liability->amount;
                if (!isset($this->result->deductions->positionTax)) {
                    $this->result->deductions->set('positionTax', $this->result->earnings->monthly->positionTax);
                }
                $this->result->deductions->set('pph21Tax', $this->result->taxable->pph->liability->monthly);
                $this->result->deductions->set('pph21Resign', $this->result->taxable->pphResign->liability->amount);
                break;
            // Pajak ditanggung oleh perusahaan sebagai tunjangan pajak.
            case CalculationConstants::GROSS_UP:
                $this->result->takeHomePay = $this->result->earnings->base + $this->result->earnings->fixedAllowance + $this->result->taxable->pph->liability->monthly + $this->result->resign->amount + $this->result->allowances->sum() - ($this->result->deductions->sum() + $this->result->taxable->pph->liability->monthly);
                $this->result->deductions->set('positionTax', $this->result->earnings->monthly->positionTax);
                $this->result->deductions->set('pph21Tax', $this->result->taxable->pph->liability->monthly);
                $this->result->allowances->set('positionTax', $this->result->earnings->monthly->positionTax);
                $this->result->allowances->set('pph21Tax', $this->result->taxable->pph->liability->monthly);
                $this->result->deductions->set('pph21Resign', $this->result->taxable->pphResign->liability->amount);
                $this->result->allowances->set('pph21Resign', $this->result->taxable->pphResign->liability->amount);
                break;
            default:
                // Default case for safety
                $this->result->takeHomePay = $this->result->earnings->monthly->nett;
                break;
        }

        return $this->result;
    }

    private function calculateBaseOnPph23(): Result
    {
        // Gaji + Penghasilan teratur
        $this->result->earnings->base = $this->employee->earnings->base;
        $this->result->earnings->fixedAllowance = $this->employee->earnings->fixedAllowance;

        // Penghasilan bruto bulanan merupakan gaji pokok ditambah tunjangan tetap
        $this->result->earnings->monthly->gross = $this->result->earnings->base + $this->employee->earnings->fixedAllowance;

        // Penghasilan tidak teratur - overtime
        if (true === $this->provisions->company->calculateOvertime) {
            $overtimeCalculation = OvertimeCalculator::overtimeCalculation(
                $this->employee->earnings->base,
                $this->employee->overtimes->details,
                $this->employee->overtimes->summary
            );

            $this->result->earnings->overtime = new OvertimeResult(
                payment: round($overtimeCalculation->earning),
                adjustment: $overtimeCalculation->adjustment ?? 0,
                note: $overtimeCalculation->note,
                reports: $overtimeCalculation->reports,
            );
            $this->result->set('overtimeReports', $overtimeCalculation->reports);
            $this->result->set('overtimeNote', $overtimeCalculation->note);
            $this->result->set('overtimeAdjustment', $overtimeCalculation->adjustment);

            // Lembur ditambahkan sebagai pendapatan bruto bulanan
            $this->result->earnings->monthly->gross += round($overtimeCalculation->earning);
        }

        // Set result allowances, bonus, deductions
        $this->result->allowances = $this->employee->components->allowances;
        $this->result->onetime->bonus = $this->employee->onetime->bonus;
        $this->result->deductions = $this->employee->components->deductions;

        $this->result->earnings->monthly->gross += ($this->result->allowances->sum() + $this->result->onetime->bonus);

        // Pendapatan bersih
        $this->result->earnings->monthly->nett = $this->result->earnings->monthly->gross - $this->result->deductions->sum();
        $this->result->earnings->annualy->nett = $this->result->earnings->monthly->nett * $this->employee->monthMultiplier;

        // Calculate PPh23
        $this->result->taxable = (new Pph23($this))->calculate();

        switch ($this->method) {
            // Pajak ditanggung oleh perusahaan
            case CalculationConstants::NETT:
                $this->result->takeHomePay = $this->result->earnings->monthly->nett +
                    $this->result->onetime->bonus;
                $this->company->set('pph23Tax', $this->result->taxable->pph23->liability->amount);
                break;
            // Pajak ditanggung oleh karyawan
            case CalculationConstants::GROSS:
                $this->result->takeHomePay = $this->result->earnings->monthly->nett +
                    $this->result->onetime->bonus -
                    $this->result->taxable->pph23->liability->amount;
                $this->result->deductions->set('pph23Tax', $this->result->taxable->pph23->liability->amount);
                break;
            // Pajak ditanggung oleh perusahaan dengan grossup
            case CalculationConstants::GROSS_UP:
                $this->result->takeHomePay = $this->result->earnings->monthly->nett +
                    $this->result->onetime->bonus;
                $this->result->allowances->set('pph23Tax', $this->result->taxable->pph23->liability->amount);
                break;
            default:
                // Default case for safety
                $this->result->takeHomePay = $this->result->earnings->monthly->nett;
                break;
        }

        return $this->result;
    }

    private function calculateBaseOnPph26(): Result
    {
        // Gaji + Penghasilan teratur
        $this->result->earnings->base = $this->employee->earnings->base;
        $this->result->earnings->fixedAllowance = $this->employee->earnings->fixedAllowance;

        // Penghasilan bruto bulanan merupakan gaji pokok ditambah tunjangan tetap
        $this->result->earnings->monthly->gross = $this->result->earnings->base + $this->employee->earnings->fixedAllowance;

        // Penghasilan tidak teratur - overtime
        if (true === $this->provisions->company->calculateOvertime) {
            $overtimeCalculation = OvertimeCalculator::overtimeCalculation(
                $this->employee->earnings->base,
                $this->employee->overtimes->details,
                $this->employee->overtimes->summary
            );

            $this->result->earnings->overtime = new OvertimeResult(
                payment: round($overtimeCalculation->earning),
                adjustment: $overtimeCalculation->adjustment ?? 0,
                note: $overtimeCalculation->note,
                reports: $overtimeCalculation->reports,
            );
            $this->result->set('overtimeReports', $overtimeCalculation->reports);
            $this->result->set('overtimeNote', $overtimeCalculation->note);
            $this->result->set('overtimeAdjustment', $overtimeCalculation->adjustment);

            // Lembur ditambahkan sebagai pendapatan bruto bulanan
            $this->result->earnings->monthly->gross += round($overtimeCalculation->earning);
        }

        // Set result allowances, bonus, deductions
        $this->result->allowances = $this->employee->components->allowances;
        $this->result->onetime->bonus = $this->employee->onetime->bonus;
        $this->result->deductions = $this->employee->components->deductions;

        $this->result->earnings->monthly->gross += ($this->result->allowances->sum() + $this->result->onetime->bonus);

        // Pendapatan bersih
        $this->result->earnings->monthly->nett = $this->result->earnings->monthly->gross - $this->result->deductions->sum();
        $this->result->earnings->annualy->nett = $this->result->earnings->monthly->nett * $this->employee->monthMultiplier;

        // Calculate PPh26
        $this->result->taxable = (new Pph26($this))->calculate();

        switch ($this->method) {
            // Pajak ditanggung oleh perusahaan
            case CalculationConstants::NETT:
                $this->result->takeHomePay = $this->result->earnings->monthly->nett +
                    $this->employee->onetime->bonus;
                $this->company->set('pph26Tax', $this->result->taxable->pph26->liability->amount);
                break;
            // Pajak ditanggung oleh karyawan
            case CalculationConstants::GROSS:
                $this->result->takeHomePay = $this->result->earnings->monthly->nett +
                    $this->employee->onetime->bonus -
                    $this->result->taxable->pph26->liability->amount;
                $this->result->deductions->set('pph26Tax', $this->result->taxable->pph26->liability->amount);
                break;
            // Pajak ditanggung oleh perusahaan sebagai tunjangan pajak.
            case CalculationConstants::GROSS_UP:
                $this->result->takeHomePay = $this->result->earnings->monthly->nett +
                    $this->employee->onetime->bonus;
                $this->result->allowances->set('pph26Tax', $this->result->taxable->pph26->liability->amount);
                break;
            default:
                // Default case for safety
                $this->result->takeHomePay = $this->result->earnings->monthly->nett;
                break;
        }

        return $this->result;
    }
}
