<?php

declare(strict_types=1);

namespace Comfino\Common\Shop\Product\CategoryTree;

interface BuildStrategyInterface
{
    /**
     * @return Descriptor
     */
    public function build(): Descriptor;
}
