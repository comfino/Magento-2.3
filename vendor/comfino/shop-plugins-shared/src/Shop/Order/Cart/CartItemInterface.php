<?php

declare(strict_types=1);

namespace Comfino\Shop\Order\Cart;

interface CartItemInterface
{
    public function getProduct(): ProductInterface;

    public function getQuantity(): int;
}
