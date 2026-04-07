<?php

declare(strict_types=1);

namespace Comfino\Shop\Order\Cart;

class CartItem implements CartItemInterface
{
    /**
     * @var ProductInterface
     */
    private $product;
    /**
     * @var int
     */
    private $quantity;
    /**
     * @param ProductInterface $product
     * @param int $quantity
     */
    public function __construct(ProductInterface $product, int $quantity)
    {
        $this->product = $product;
        $this->quantity = $quantity;
    }

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
