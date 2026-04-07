<?php

namespace Comfino\ComfinoGateway\Observer;

use Comfino\ComfinoGateway\Model\Connector\Service\ApplicationService;
use Comfino\Configuration\ConfigManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Model\Order;

/**
 * Listens for order save events and cancels the Comfino application when a Comfino order is canceled.
 *
 * Two triggers are handled:
 *
 *   1. State-based: the order state transitions to STATE_CANCELED from any other state. This covers programmatic
 *      cancellations (e.g. webhook-driven) and covers all custom Comfino cancellation statuses, because each of them
 *      is assigned to STATE_CANCELED in the database.
 *
 *   2. Status-based fallback: a Comfino cancellation status is set directly (e.g. via admin panel) on an order whose
 *      state was not previously STATE_CANCELED. This guard is needed for the rare case where admin manually selects a
 *      cancellation status without the state transition being detected first.
 *
 * @see ApplicationService::cancelApplicationTransaction()
 */
class OrderObserver implements ObserverInterface
{
    /**
     * Custom Comfino status codes that represent a cancellation event.
     * Mirrors the cancellation entries in ShopStatusManager::CUSTOM_STATUS_MAP.
     *
     * @var string[]
     */
    private const COMFINO_CANCELLED_STATUSES = [
        'comfino_cancelled_by_shop',
        'comfino_cancelled',
        'comfino_rejected',
    ];

    private ApplicationService $applicationService;

    public function __construct(ApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order instanceof AbstractModel) {
            if (($payment = $order->getPayment()) === null) {
                return;
            }

            if ($payment->getMethod() === 'comfino') {
                $currentState = $order->getState();
                $previousState = $order->getOrigData('state');

                // Trigger 1: order state changed to canceled.
                if ($currentState === Order::STATE_CANCELED && $previousState !== Order::STATE_CANCELED) {
                    $this->applicationService->cancelApplicationTransaction(
                        ConfigManager::isUseOrderReference()
                            ? (!empty($order->getIncrementId()) ? $order->getIncrementId() : (string) $order->getId())
                            : (string) $order->getId()
                    );

                    return;
                }

                // Trigger 2: a Comfino cancellation status was applied via admin without a state transition.
                $currentStatus = $order->getStatus();
                $previousStatus = $order->getOrigData('status');

                if ($previousState !== Order::STATE_CANCELED &&
                    in_array($currentStatus, self::COMFINO_CANCELLED_STATUSES, true) &&
                    !in_array($previousStatus, self::COMFINO_CANCELLED_STATUSES, true)
                ) {
                    $this->applicationService->cancelApplicationTransaction(
                        ConfigManager::isUseOrderReference()
                            ? (!empty($order->getIncrementId()) ? $order->getIncrementId() : (string) $order->getId())
                            : (string) $order->getId()
                    );
                }
            }
        }
    }
}
