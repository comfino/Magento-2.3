<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Payment\ProductTypeFilter;

use Comfino\Common\Backend\Payment\ProductTypeFilterInterface;

class FilterByCartValueLowerLimit implements ProductTypeFilterInterface
{
    /**
     * @var int[]
     */
    private $cartValueLimitsByProductType;
    /**
     * @param int[] $cartValueLimitsByProductType
     */
    public function __construct(array $cartValueLimitsByProductType)
    {
        $this->cartValueLimitsByProductType = $cartValueLimitsByProductType;
    }

    /**
     * @param mixed[] $availableProductTypes
     * @param \Comfino\Common\Shop\Cart $cart
     */
    public function getAllowedProductTypes($availableProductTypes, $cart): array
    {
        $allowedProductTypes = [];

        foreach ($availableProductTypes as $productType) {
            if (array_key_exists((string) $productType, $this->cartValueLimitsByProductType)) {
                if ($cart->getTotalValue() >= $this->cartValueLimitsByProductType[(string) $productType]) {
                    $allowedProductTypes[] = $productType;
                }
            } else {
                $allowedProductTypes[] = $productType;
            }
        }

        return $allowedProductTypes;
    }

    public function getAsArray(): array
    {
        return ['cartValueLimitsByProductType' => $this->cartValueLimitsByProductType];
    }
}
