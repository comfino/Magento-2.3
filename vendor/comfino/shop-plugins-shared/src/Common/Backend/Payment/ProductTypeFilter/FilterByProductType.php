<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Payment\ProductTypeFilter;

use Comfino\Api\Dto\Payment\LoanTypeEnum;
use Comfino\Common\Backend\Payment\ProductTypeFilterInterface;

class FilterByProductType implements ProductTypeFilterInterface
{
    /**
     * @var LoanTypeEnum[]
     */
    private $allowedProductTypes;
    /**
     * @param LoanTypeEnum[] $allowedProductTypes
     */
    public function __construct(array $allowedProductTypes)
    {
        $this->allowedProductTypes = $allowedProductTypes;
    }

    /**
     * @param mixed[] $availableProductTypes
     * @param \Comfino\Common\Shop\Cart $cart
     */
    public function getAllowedProductTypes($availableProductTypes, $cart): array
    {
        return array_intersect($this->allowedProductTypes, $availableProductTypes);
    }

    public function getAsArray(): array
    {
        return ['allowedProductTypes' => $this->allowedProductTypes];
    }
}
