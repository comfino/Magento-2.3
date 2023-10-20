<?php

namespace Comfino\ComfinoGateway\Api;

use Magento\Sales\Api\Data\OrderInterface;

interface ComfinoStatusManagementInterface
{
    /**
     * @param int $applicationId
     * @param string $orderStatus
     */
    public function changeApplicationAndOrderStatus(int $applicationId, string $orderStatus): bool;

    /**
     * @param OrderInterface $order
     */
    public function applicationFailureStatus(OrderInterface $order): void;
}
