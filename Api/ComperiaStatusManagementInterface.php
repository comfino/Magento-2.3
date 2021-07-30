<?php

namespace Comperia\ComperiaGateway\Api;

interface ComperiaStatusManagementInterface
{
    /**
     * @param int $applicationId
     * @param string $orderStatus
     */
    public function changeApplicationAndOrderStatus(int $applicationId, string $orderStatus): void;

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    public function applicationFailureStatus(\Magento\Sales\Api\Data\OrderInterface $order): void;
}
