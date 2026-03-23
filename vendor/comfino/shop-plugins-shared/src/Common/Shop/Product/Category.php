<?php

declare(strict_types=1);

namespace Comfino\Common\Shop\Product;

final class Category
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var int
     */
    public $position;
    /**
     * @var Category[]
     */
    public $children;
    /**
     * @param int $id
     * @param string $name
     * @param int $position
     * @param Category[] $children
     */
    public function __construct(int $id, string $name, int $position, array $children)
    {
        $this->id = $id;
        $this->name = $name;
        $this->position = $position;
        $this->children = $children;
    }
}
