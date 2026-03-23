<?php

declare(strict_types=1);

namespace Comfino\Common\Shop\Product\CategoryTree;

final class Node
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var Node|null
     */
    private $parent;
    /**
     * @var NodeIterator|null
     */
    private $children;
    /**
     * @param int $id
     * @param string $name
     * @param Node|null $parent
     * @param NodeIterator|null $children
     */
    public function __construct(int $id, string $name, ?Node $parent = null, ?NodeIterator $children = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->parent = $parent;
        $this->children = $children;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Node|null
     */
    public function getParent(): ?Node
    {
        return $this->parent;
    }

    /**
     * @param Node|null $parent
     */
    public function setParent(?Node $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return NodeIterator|null
     */
    public function getChildren(): ?NodeIterator
    {
        return $this->children;
    }

    /**
     * @param NodeIterator $children
     */
    public function setChildren(NodeIterator $children): void
    {
        $this->children = $children;
    }

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->parent === null;
    }

    /**
     * @return bool
     */
    public function isLeaf(): bool
    {
        return !$this->hasChildren();
    }

    /**
     * @param Node $node
     * @return bool
     */
    public function isParentOf(Node $node): bool
    {
        return $this === $node->getParent();
    }

    /**
     * @param Node $node
     * @return bool
     */
    public function isChildOf(Node $node): bool
    {
        return $node === $this->parent;
    }

    /**
     * @param Node $node
     * @return bool
     */
    public function isAncestorOf(Node $node): bool
    {
        if ($this->isParentOf($node)) {
            return true;
        }

        if ($this->isLeaf()) {
            return false;
        }

        foreach ($this->children as $childNode) {
            if ($childNode->isAncestorOf($node)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Node $node
     * @return bool
     */
    public function isDescendantOf(Node $node): bool
    {
        if ($this->isChildOf($node)) {
            return true;
        }

        if ($this->isRoot()) {
            return false;
        }

        $parentNode = $this->parent;

        while ($parentNode !== null) {
            if ($parentNode === $node) {
                return true;
            }

            $parentNode = $parentNode->getParent();
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children !== null && $this->children->count() !== 0;
    }

    /**
     * @return NodeIterator
     */
    public function getPathToRoot(): NodeIterator
    {
        $nodes = [];
        $node = $this;

        do {
            $nodes[] = $node;
        } while (($node = $node->getParent()) !== null);

        return new NodeIterator($nodes);
    }
}
