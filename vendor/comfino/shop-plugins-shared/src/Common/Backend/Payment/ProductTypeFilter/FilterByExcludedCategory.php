<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Payment\ProductTypeFilter;

use Comfino\Common\Backend\Payment\ProductTypeFilterInterface;
use Comfino\Common\Shop\Product\CategoryFilter;

class FilterByExcludedCategory implements ProductTypeFilterInterface
{
    /**
     * @var \Comfino\Common\Shop\Product\CategoryFilter
     */
    private $categoryFilter;
    /**
     * @var int[][]
     */
    private $excludedCategoryIdsByProductType;
    /**
     * @param int[][] $excludedCategoryIdsByProductType
     */
    public function __construct(CategoryFilter $categoryFilter, array $excludedCategoryIdsByProductType)
    {
        $this->categoryFilter = $categoryFilter;
        $this->excludedCategoryIdsByProductType = $excludedCategoryIdsByProductType;
    }

    /**
     * @param mixed[] $availableProductTypes
     * @param \Comfino\Common\Shop\Cart $cart
     */
    public function getAllowedProductTypes($availableProductTypes, $cart): array
    {
        $allowedProductTypes = [];

        foreach ($availableProductTypes as $productType) {
            if (array_key_exists((string) $productType, $this->excludedCategoryIdsByProductType)) {
                if ($this->categoryFilter->isCartValid($cart, $this->excludedCategoryIdsByProductType[(string) $productType])) {
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
        return ['excludedCategoryIdsByProductType' => $this->excludedCategoryIdsByProductType];
    }
}
