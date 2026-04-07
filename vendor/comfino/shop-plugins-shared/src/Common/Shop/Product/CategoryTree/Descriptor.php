<?php

declare(strict_types=1);

namespace Comfino\Common\Shop\Product\CategoryTree;

final class Descriptor
{
    /**
     * @var NodeIterator
     */
    public $nodes;
    /**
     * @var Node[]|null
     */
    public $index;
    /**
     * @param NodeIterator $nodes
     * @param Node[]|null $index
     */
    public function __construct(NodeIterator $nodes, ?array $index)
    {
        $this->nodes = $nodes;
        $this->index = $index;
    }
}
