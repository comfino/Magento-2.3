<?php

declare(strict_types=1);

namespace Comfino\Shop\Order;

interface SellerInterface
{
    public function getTaxId(): ?string;
}
