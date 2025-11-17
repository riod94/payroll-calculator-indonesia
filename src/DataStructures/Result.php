<?php

declare(strict_types=1);

namespace PayrollCalculator\DataStructures;

use PayrollCalculator\Traits\MapArrayObject;

class Result
{
    use MapArrayObject;

    public function __construct(
        public float $takeHomePay = 0,
        public EarningsResult $earnings = new EarningsResult(),
        public BonusResult $bonus = new BonusResult(),
        public ResignResult $resign = new ResignResult(),
        public TaxableResult $taxable = new TaxableResult(),
        public Allowances $allowances = new Allowances(),
        public Deductions $deductions = new Deductions(),
        public Company $company = new Company(),
        public Onetime $onetime = new Onetime(),
    ) {}
}

class EarningsResult
{
    use MapArrayObject;

    public function __construct(
        public float $base = 0,
        public float $fixedAllowance = 0,
        public AnnualResult $annualy = new AnnualResult(),
        public MonthlyResult $monthly = new MonthlyResult(),
        public OvertimeResult $overtime = new OvertimeResult(),
    ) {}
}

class AnnualResult
{
    use MapArrayObject;

    public function __construct(
        public float $nett = 0,
        public float $gross = 0,
        public float $positionTax = 0,
    ) {}
}

class MonthlyResult
{
    use MapArrayObject;

    public function __construct(
        public float $nett = 0,
        public float $gross = 0,
        public float $positionTax = 0,
    ) {}
}

class OvertimeResult
{
    use MapArrayObject;

    public function __construct(
        public float $payment = 0,
        public float $adjustment = 0,
        public ?string $note = null,
        public array $reports = [],
    ) {}
}

class BonusResult
{
    use MapArrayObject;

    public function __construct(
        public MonthlyResult $monthly = new MonthlyResult(),
        public AnnualResult $annualy = new AnnualResult(),
    ) {}
}

class ResignResult
{
    use MapArrayObject;

    public function __construct(
        public float $amount = 0,
        public ResignAllowances $allowances = new ResignAllowances(),
    ) {}
}

class ResignAllowances
{
    use MapArrayObject;

    public function __construct(
        public float $compensationPay = 0,
        public float $severancePay = 0,
        public float $meritPay = 0,
    ) {}
}

class TaxableResult
{
    use MapArrayObject;

    public function __construct(
        public PphResult $pph = new PphResult(),
        public PphResult $pphBonus = new PphResult(),
        public PphResignResult $pphResign = new PphResignResult(),
        public PphResult $pph26 = new PphResult(),
        public PphResult $pph23 = new PphResult(),
    ) {}
}

class PphResult
{
    use MapArrayObject;

    public function __construct(
        public PtkpResult $ptkp = new PtkpResult(),
        public float $pkp = 0,
        public ParseTerDetailType $ter = new ParseTerDetailType(),
        public LiabilityResult $liability = new LiabilityResult(),
    ) {}
}

class PtkpResult
{
    use MapArrayObject;

    public function __construct(
        public string $status = '',
        public float $amount = 0,
    ) {}
}

class ParseTerDetailType
{
    use MapArrayObject;

    public function __construct(
        public float $gross = 0,
        public string $category = '',
        public float $from = 0,
        public float $until = 0,
        public float $rate = 0,
    ) {}
}

class LiabilityResult
{
    use MapArrayObject;

    public function __construct(
        public int $rule = 0,
        public float $monthly = 0,
        public float $annual = 0,
        public float $monthlyGrossUp = 0,
        public float $amount = 0,
    ) {}
}

class PphResignResult
{
    use MapArrayObject;

    public function __construct(
        public PtkpResult $ptkp = new PtkpResult(),
        public float $pkp = 0,
        public ParseTerDetailType $ter = new ParseTerDetailType(),
        public ResignLiabilityResult $liability = new ResignLiabilityResult(),
    ) {}
}

class ResignLiabilityResult
{
    use MapArrayObject;

    public function __construct(
        public int $rule = 0,
        public float $amount = 0,
    ) {}
}
