<?php

declare(strict_types=1);

namespace Comfino\Shop\Order;

interface CartInterface
{
    public function getItems(): array;

    public function getTotalAmount(): int;

    public function getDeliveryCost(): ?int;

    public function getDeliveryNetCost(): ?int;

    public function getDeliveryCostTaxRate(): ?int;

    public function getDeliveryCostTaxValue(): ?int;

    public function getCategory(): ?string;
}
