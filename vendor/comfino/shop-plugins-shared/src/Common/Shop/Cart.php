<?php

declare(strict_types=1);

namespace Comfino\Common\Shop;

use Comfino\Shop\Order\Cart\CartItemInterface;

class Cart
{
    /**
     * @var int
     */
    private $totalValue;
    /**
     * @var int|null
     */
    private $totalNetValue;
    /**
     * @var int|null
     */
    private $totalTaxValue;
    /**
     * @var int
     */
    private $deliveryCost;
    /**
     * @var int|null
     */
    private $deliveryNetCost;
    /**
     * @var int|null
     */
    private $deliveryTaxRate;
    /**
     * @var int|null
     */
    private $deliveryTaxValue;
    /**
     * @var CartItemInterface[]
     */
    private $cartItems;

    private $cartCategoryIds;

    /**
     * @param CartItemInterface[] $cartItems
     */
    public function __construct(int $totalValue, ?int $totalNetValue, ?int $totalTaxValue, int $deliveryCost, ?int $deliveryNetCost, ?int $deliveryTaxRate, ?int $deliveryTaxValue, array $cartItems)
    {
        $this->totalValue = $totalValue;
        $this->totalNetValue = $totalNetValue;
        $this->totalTaxValue = $totalTaxValue;
        $this->deliveryCost = $deliveryCost;
        $this->deliveryNetCost = $deliveryNetCost;
        $this->deliveryTaxRate = $deliveryTaxRate;
        $this->deliveryTaxValue = $deliveryTaxValue;
        $this->cartItems = $cartItems;
    }

    public function getTotalValue(): int
    {
        return $this->totalValue;
    }

    public function getTotalNetValue(): ?int
    {
        return $this->totalNetValue;
    }

    public function getTotalTaxValue(): ?int
    {
        return $this->totalTaxValue;
    }

    public function getDeliveryCost(): int
    {
        return $this->deliveryCost;
    }

    public function getDeliveryNetCost(): ?int
    {
        return $this->deliveryNetCost;
    }

    public function getDeliveryTaxRate(): ?int
    {
        return $this->deliveryTaxRate;
    }

    public function getDeliveryTaxValue(): ?int
    {
        return $this->deliveryTaxValue;
    }

    /**
     * @return CartItemInterface[]
     */
    public function getCartItems(): array
    {
        return $this->cartItems;
    }

    /**
     * @return int[]
     */
    public function getCartCategoryIds(): array
    {
        if ($this->cartCategoryIds !== null) {
            return $this->cartCategoryIds;
        }

        $categoryIds = [];

        foreach ($this->cartItems as $cartItem) {
            if (($productCategoryIds = $cartItem->getProduct()->getCategoryIds()) !== null) {
                $categoryIds[] = $productCategoryIds;
            }
        }

        return ($this->cartCategoryIds = array_unique(array_merge([], ...$categoryIds), SORT_NUMERIC));
    }

    /**
     * @param bool $withNulls
     */
    public function getAsArray($withNulls = true): array
    {
        $cart = [
            'totalAmount' => $this->totalValue,
            'deliveryCost' => $this->deliveryCost,
            'deliveryNetCost' => $this->deliveryNetCost,
            'deliveryCostVatRate' => $this->deliveryTaxRate,
            'deliveryCostVatAmount' => $this->deliveryTaxValue,
            'products' => array_map(
                static function (CartItemInterface $cartItem) use ($withNulls): array {
                    $product = [
                        'name' => $cartItem->getProduct()->getName(),
                        'quantity' => $cartItem->getQuantity(),
                        'price' => $cartItem->getProduct()->getPrice(),
                        'netPrice' => $cartItem->getProduct()->getNetPrice(),
                        'vatRate' => $cartItem->getProduct()->getTaxRate(),
                        'vatAmount' => $cartItem->getProduct()->getTaxValue(),
                        'externalId' => $cartItem->getProduct()->getId(),
                        'category' => $cartItem->getProduct()->getCategory(),
                        'ean' => $cartItem->getProduct()->getEan(),
                        'photoUrl' => $cartItem->getProduct()->getPhotoUrl(),
                        'categoryIds' => $cartItem->getProduct()->getCategoryIds(),
                    ];

                    return $withNulls ? $product : array_filter($product, static function ($productFieldValue): bool {
                        return $productFieldValue !== null;
                    });
                },
                $this->cartItems
            ),
        ];

        return $withNulls ? $cart : array_filter($cart, static function ($cartFieldValue): bool {
            return $cartFieldValue !== null;
        });
    }
}
