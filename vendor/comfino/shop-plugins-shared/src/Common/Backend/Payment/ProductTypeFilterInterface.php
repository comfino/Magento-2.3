<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Payment;

use Comfino\Api\Dto\Payment\LoanTypeEnum;

interface ProductTypeFilterInterface
{
    /**
     * @param LoanTypeEnum[] $availableProductTypes
     * @return LoanTypeEnum[]
     * @param \Comfino\Common\Shop\Cart $cart
     */
    public function getAllowedProductTypes($availableProductTypes, $cart): array;

    public function getAsArray(): array;
}
