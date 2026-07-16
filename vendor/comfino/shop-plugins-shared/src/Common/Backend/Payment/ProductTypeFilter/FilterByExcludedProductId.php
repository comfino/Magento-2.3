<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Payment\ProductTypeFilter;

use Comfino\Common\Backend\Payment\ProductTypeFilterInterface;

class FilterByExcludedProductId implements ProductTypeFilterInterface
{
    /**
     * @var int[]
     */
    private $excludedProductIds;

    /**
     * @param int[] $excludedProductIds
     */
    public function __construct(array $excludedProductIds)
    {
        $this->excludedProductIds = array_map('intval', $excludedProductIds);
    }

    /**
     * @param mixed[] $availableProductTypes
     * @param \Comfino\Common\Shop\Cart $cart
     */
    public function getAllowedProductTypes($availableProductTypes, $cart): array
    {
        if (empty($this->excludedProductIds)) {
            return $availableProductTypes;
        }

        foreach ($cart->getCartItems() as $cartItem) {
            if (in_array((int) $cartItem->getProduct()->getId(), $this->excludedProductIds, true)) {
                return [];
            }
        }

        return $availableProductTypes;
    }

    public function getAsArray(): array
    {
        return ['excludedProductIds' => $this->excludedProductIds];
    }
}
