<?php

declare(strict_types=1);

namespace Comfino\Common\Shop\Product;

use Comfino\Common\Shop\Product\CategoryTree\Descriptor;
use Comfino\Common\Shop\Product\CategoryTree\Node;
use Comfino\Common\Shop\Product\CategoryTree\NodeIterator;

class CategoryManager
{
    /**
     * @param Category[] $nestedCategories
     * @return Descriptor
     */
    public static function buildCategoryTree($nestedCategories): Descriptor
    {
        $nodes = [];
        $index = [];

        foreach ($nestedCategories as $category) {
            $node = new Node($category->id, $category->name);

            if (!empty($category->children)) {
                $childNodes = [];

                foreach ($category->children as $childCategory) {
                    $childNodes[] = self::processCategory($node, $childCategory, $index);
                }

                $node->setChildren(new NodeIterator($childNodes));
            }

            $nodes[] = $node;
            $index[$node->getId()] = $node;
        }

        return new Descriptor(new NodeIterator($nodes), $index);
    }

    /**
     * @param Node $parentNode
     * @param Category $category
     * @return Node
     */
    private static function processCategory(Node $parentNode, Category $category, array &$index): Node
    {
        $node = new Node($category->id, $category->name, $parentNode);

        if (!empty($category->children)) {
            $childNodes = [];

            foreach ($category->children as $childCategory) {
                $childNodes[] = self::processCategory($node, $childCategory, $index);
            }

            $node->setChildren(new NodeIterator($childNodes));
        }

        $index[$node->getId()] = $node;

        return $node;
    }
}
