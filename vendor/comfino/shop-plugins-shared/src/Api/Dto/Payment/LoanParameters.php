<?php

declare(strict_types=1);

namespace Comfino\Api\Dto\Payment;

class LoanParameters
{
    public $instalmentAmount;

    public $toPay;

    public $loanTerm;

    public $rrso;

    public $initialPaymentValue;

    public $initialPaymentRate;

    public $redemptionPaymentValue;

    public $redemptionPaymentRate;

    public $interest;

    /**
     * @param int $instalmentAmount
     * @param int $toPay
     * @param int $loanTerm
     * @param float $rrso
     * @param int|null $initialPaymentValue
     * @param float|null $initialPaymentRate
     * @param int|null $redemptionPaymentValue
     * @param float|null $redemptionPaymentRate
     * @param float|null $interest
     */
    public function __construct(
        int $instalmentAmount,
        int $toPay,
        int $loanTerm,
        float $rrso,
        ?int $initialPaymentValue = null,
        ?float $initialPaymentRate = null,
        ?int $redemptionPaymentValue = null,
        ?float $redemptionPaymentRate = null,
        ?float $interest = null
    ) {
        $this->instalmentAmount = $instalmentAmount;
        $this->toPay = $toPay;
        $this->loanTerm = $loanTerm;
        $this->rrso = $rrso;
        $this->initialPaymentValue = $initialPaymentValue;
        $this->initialPaymentRate = $initialPaymentRate;
        $this->redemptionPaymentValue = $redemptionPaymentValue;
        $this->redemptionPaymentRate = $redemptionPaymentRate;
        $this->interest = $interest;
    }
}
