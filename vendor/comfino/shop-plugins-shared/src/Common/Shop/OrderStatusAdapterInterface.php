<?php

declare(strict_types=1);

namespace Comfino\Common\Shop;

interface OrderStatusAdapterInterface
{
    /**
     * @param string $orderId
     * @param string $status
     */
    public function setStatus($orderId, $status): void;
}
