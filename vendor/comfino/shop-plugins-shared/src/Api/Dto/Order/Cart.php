<?php

declare(strict_types=1);

namespace Comfino\Api\Dto\Order;

use Comfino\Api\Dto\Order\Cart\CartItem;

class Cart
{
    public $totalAmount;

    public $deliveryCost;

    public $category;

    public $products;

    /**
     * @param int $totalAmount
     * @param int $deliveryCost
     * @param string|null $category
     * @param CartItem[] $products
     */
    public function __construct(int $totalAmount, int $deliveryCost, ?string $category, array $products)
    {
        $this->totalAmount = $totalAmount;
        $this->deliveryCost = $deliveryCost;
        $this->category = $category;
        $this->products = $products;
    }
}
