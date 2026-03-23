<?php

declare(strict_types=1);

namespace Comfino\Shop\Order;

use Comfino\Shop\Order\Cart\CartItemInterface;

class Cart implements CartInterface
{
    /**
     * @var CartItemInterface[]
     */
    private $items;
    /**
     * @var int
     */
    private $totalAmount;
    /**
     * @var int|null
     */
    private $deliveryCost;
    /**
     * @var int|null
     */
    private $deliveryNetCost;
    /**
     * @var int|null
     */
    private $deliveryCostTaxRate;
    /**
     * @var int|null
     */
    private $deliveryCostTaxValue;
    /**
     * @var string|null
     */
    private $category;
    /**
     * @param CartItemInterface[] $items
     * @param int $totalAmount
     * @param int|null $deliveryCost
     * @param int|null $deliveryNetCost
     * @param int|null $deliveryCostTaxRate
     * @param int|null $deliveryCostTaxValue
     * @param string|null $category
     */
    public function __construct(array $items, int $totalAmount, ?int $deliveryCost = null, ?int $deliveryNetCost = null, ?int $deliveryCostTaxRate = null, ?int $deliveryCostTaxValue = null, ?string $category = null)
    {
        $this->items = $items;
        $this->totalAmount = $totalAmount;
        $this->deliveryCost = $deliveryCost;
        $this->deliveryNetCost = $deliveryNetCost;
        $this->deliveryCostTaxRate = $deliveryCostTaxRate;
        $this->deliveryCostTaxValue = $deliveryCostTaxValue;
        $this->category = $category;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function getDeliveryCost(): ?int
    {
        return $this->deliveryCost;
    }

    public function getDeliveryNetCost(): ?int
    {
        return $this->deliveryNetCost;
    }

    public function getDeliveryCostTaxRate(): ?int
    {
        return $this->deliveryCostTaxRate;
    }

    public function getDeliveryCostTaxValue(): ?int
    {
        return $this->deliveryCostTaxValue;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }
}
