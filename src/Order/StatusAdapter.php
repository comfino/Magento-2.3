<?php

namespace Comfino\Order;

use Comfino\Common\Shop\Order\StatusManager;
use Comfino\Common\Shop\OrderStatusAdapterInterface;
use Comfino\Configuration\ConfigManager;
use Comfino\DebugLogger;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderRepository;

/**
 * Adapter for handling order status updates from Comfino API webhooks.
 *
 * Implements OrderStatusAdapterInterface from the shared library and provides Magento-specific logic for updating
 * order statuses based on payment status changes received from Comfino API.
 *
 * Uses Magento's native state/status two-level system: each Comfino API status is mapped to a custom
 * status code (e.g. "comfino_accepted") that is pre-assigned to the correct Magento state (e.g. processing)
 * in the database. The custom status becomes the final, persistent label visible in the admin order grid,
 * so merchants see "Credit granted (Comfino)" rather than the generic "Processing".
 *
 * Status flow:
 *   - Determine the custom status code from ConfigManager::getStatusMap() (configurable; defaults to ShopStatusManager::DEFAULT_STATUS_MAP).
 *   - Determine the target Magento state from ShopStatusManager::CUSTOM_STATUS_LABELS.
 *   - Set order state + custom status in one call, add one history entry with the Comfino status string.
 *   - For ACCEPTED on virtual-only orders: additionally create an offline invoice so Magento
 *     auto-transitions the order to STATE_COMPLETE (nothing to ship) and grants access to virtual/downloadable items.
 *
 * @see OrderStatusAdapterInterface
 * @see StatusManager
 * @see ShopStatusManager
 */
class StatusAdapter implements OrderStatusAdapterInterface
{
    private OrderRepository $orderRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    public function __construct(OrderRepository $orderRepository, SearchCriteriaBuilder $searchCriteriaBuilder)
    {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Updates Magento order status based on Comfino payment status.
     *
     * Called by the StatusNotification REST endpoint when a status webhook arrives from the Comfino API.
     * $orderId is the external order identifier that was passed to Comfino during order creation - either the
     * Magento entity_id (default) or the increment_id when COMFINO_USE_ORDER_REFERENCE is enabled.
     *
     * @param string|int $orderId Magento order entity_id or increment_id (depends on COMFINO_USE_ORDER_REFERENCE)
     * @param string $status Comfino payment status string (e.g. "ACCEPTED", "CANCELLED")
     *
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function setStatus($orderId, $status): void
    {
        DebugLogger::logEvent(
            '[ORDER_STATUS_UPDATE]',
            'StatusAdapter::setStatus: Order status update from Comfino API.',
            ['orderId' => $orderId, 'status' => $status]
        );

        $inputStatus = strtoupper($status);

        if (!in_array($inputStatus, StatusManager::STATUSES, true)) {
            DebugLogger::logEvent(
                '[ORDER_STATUS_UPDATE]',
                "StatusAdapter::setStatus: Unknown status \"$inputStatus\" - skipping."
            );

            return;
        }

        if (($customStatusCode = ConfigManager::getStatusMap()[$inputStatus] ?? null) === null) {
            // Valid Comfino status but no status mapping defined - not expected in normal flow.
            return;
        }

        // Resolve the Magento state that the custom status is assigned to.
        $customState = ShopStatusManager::CUSTOM_STATUS_LABELS[$customStatusCode]['state'] ?? Order::STATE_PENDING_PAYMENT;

        DebugLogger::logEvent(
            '[ORDER_STATUS_UPDATE]',
            sprintf(
                'StatusAdapter::setStatus (order ID: %s, status: "%s", custom status: "%s", state: "%s")',
                $orderId,
                $inputStatus,
                $customStatusCode,
                $customState
            )
        );

        if (ConfigManager::isUseOrderReference()) {
            // $orderId is an increment_id (customer-visible order number) - look up by that field.
            $items = $this->orderRepository->getList(
                $this->searchCriteriaBuilder->addFilter('increment_id', $orderId)->create()
            )->getItems();

            if (empty($items)) {
                DebugLogger::logEvent(
                    '[ORDER_STATUS_UPDATE]',
                    "StatusAdapter::setStatus: Order with increment_id \"$orderId\" not found - skipping."
                );

                return;
            }

            $order = reset($items);
        } else {
            $order = $this->orderRepository->get((int) $orderId);
        }

        /* Single-step: set Magento state + custom Comfino status simultaneously. The custom status code is pre-assigned
           to $customState in the database (AddComfinoOrderStatuses), so Magento's order workflow remains consistent. */
        $order->setState($customState)->setStatus($customStatusCode);
        $order->addStatusToHistory($customStatusCode, __('Comfino payment status: %1', $inputStatus));

        /* For ACCEPTED on virtual-only orders: create an offline invoice so that Magento's internal
           state machine can auto-transition the order to STATE_COMPLETE (no shipment required) and
           grant the customer access to virtual/downloadable items. The canInvoice() guard prevents a
           duplicate invoice if the webhook is delivered more than once. */
        if ($inputStatus === StatusManager::STATUS_ACCEPTED && $order->getIsVirtual() && $order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
            $invoice->register();
            $order->addRelatedObject($invoice);
        }

        $this->orderRepository->save($order);

        DebugLogger::logEvent(
            '[ORDER_STATUS_UPDATE]',
            "StatusAdapter::setStatus: Order $orderId status updated successfully."
        );
    }
}
