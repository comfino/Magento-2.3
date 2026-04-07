<?php

declare(strict_types=1);

namespace Comfino\Api\Dto\Payment;

class LoanQueryCriteria
{
    public $loanAmount;

    public $loanTerm;

    public $loanType;

    public $priceModifier;

    public $productTypes;

    public $taxId;

    /**
     * @param int $loanAmount
     * @param int|null $loanTerm
     * @param LoanTypeEnum|null $loanType
     * @param int|null $priceModifier
     * @param LoanTypeEnum[]|null $productTypes
     * @param string|null $taxId
     */
    public function __construct(int $loanAmount, ?int $loanTerm = null, ?LoanTypeEnum $loanType = null, ?int $priceModifier = null, ?array $productTypes = null, ?string $taxId = null)
    {
        $this->loanAmount = $loanAmount;
        $this->loanTerm = $loanTerm;
        $this->loanType = $loanType;
        $this->priceModifier = $priceModifier;
        $this->productTypes = $productTypes;
        $this->taxId = $taxId;
    }
}
