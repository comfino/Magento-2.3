<?php

namespace Comfino\ComfinoGateway\Observer;

use Comfino\ComfinoGateway\Model\Connector\Service\ApplicationService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Model\Order;

class OrderObserver implements ObserverInterface
{
    /**
     * @var ApplicationService
     */
    private $applicationService;

    public function __construct(ApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();

        if ($order instanceof AbstractModel) {
            $curState = $order->getState();
            $prevState = $order->getOrigData('state');

            if ($curState === Order::STATE_CANCELED && $prevState !== Order::STATE_CANCELED) {
                $this->applicationService->cancelApplicationTransaction($order->getId());
            }
        }
    }
}
