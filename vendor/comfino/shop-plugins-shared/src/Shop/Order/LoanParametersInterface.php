<?php

declare(strict_types=1);

namespace Comfino\Shop\Order;

use Comfino\Api\Dto\Payment\LoanTypeEnum;

interface LoanParametersInterface
{
    /**
     * @return int
     */
    public function getAmount(): int;

    /**
     * @return int|null
     */
    public function getTerm(): ?int;

    /**
     * @return LoanTypeEnum|null
     */
    public function getType(): ?LoanTypeEnum;

    /**
     * @return LoanTypeEnum[]|null
     */
    public function getAllowedProductTypes(): ?array;
}
