<?php

declare(strict_types=1);

namespace Comfino\Common\Shop\Product;

use Comfino\Common\Shop\Cart;

class CategoryFilter
{
    /**
     * @var CategoryTree
     */
    private $categoryTree;
    /**
     * @param CategoryTree $categoryTree
     */
    public function __construct(CategoryTree $categoryTree)
    {
        $this->categoryTree = $categoryTree;
    }

    /**
     * @param int $categoryId
     * @param int[] $excludedCategoryIds
     * @return bool
     */
    public function isCategoryAvailable($categoryId, $excludedCategoryIds): bool
    {
        if (in_array($categoryId, $excludedCategoryIds, true)) {
            return false;
        }

        if (($categoryNode = $this->categoryTree->getNodeById($categoryId)) === null) {
            return false;
        }

        foreach ($excludedCategoryIds as $excludedCategoryId) {
            if (($excludedCategory = $this->categoryTree->getNodeById($excludedCategoryId)) === null) {
                continue;
            }

            if ($categoryNode->isDescendantOf($excludedCategory)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Cart $cart
     * @param int[] $excludedCategoryIds
     * @return bool
     */
    public function isCartValid($cart, $excludedCategoryIds): bool
    {
        if (empty($excludedCategoryIds || empty($cart->getCartItems()))) {
            return true;
        }

        $cartCategoryIds = $cart->getCartCategoryIds();

        if (count(array_intersect($cartCategoryIds, $excludedCategoryIds)) > 0) {
            return false;
        }

        foreach ($cartCategoryIds as $categoryId) {
            if (!$this->isCategoryAvailable($categoryId, $excludedCategoryIds)) {
                return false;
            }
        }

        return true;
    }
}
