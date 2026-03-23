<?php

declare(strict_types=1);

namespace Comfino\Shop\Order;

use Comfino\Api\Dto\Payment\LoanTypeEnum;

class LoanParameters implements LoanParametersInterface
{
    /**
     * @var int
     */
    private $amount;
    /**
     * @var int|null
     */
    private $term;
    /**
     * @var LoanTypeEnum|null
     */
    private $type;
    /**
     * @var LoanTypeEnum[]|null
     */
    private $allowedProductTypes;
    /**
     * @param int $amount
     * @param int|null $term
     * @param LoanTypeEnum|null $type
     * @param LoanTypeEnum[]|null $allowedProductTypes
     */
    public function __construct(int $amount, ?int $term = null, ?LoanTypeEnum $type = null, ?array $allowedProductTypes = null)
    {
        $this->amount = $amount;
        $this->term = $term;
        $this->type = $type;
        $this->allowedProductTypes = $allowedProductTypes;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getTerm(): ?int
    {
        return $this->term;
    }

    public function getType(): ?LoanTypeEnum
    {
        return $this->type;
    }

    public function getAllowedProductTypes(): ?array
    {
        return $this->allowedProductTypes;
    }
}
