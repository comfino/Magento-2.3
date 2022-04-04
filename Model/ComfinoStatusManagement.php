<?php

namespace Comfino\ComfinoGateway\Model;

use Comfino\ComfinoGateway\Api\ComfinoStatusManagementInterface;
use Comfino\ComfinoGateway\Exception\InvalidExternalIdException;
use Comfino\ComfinoGateway\Model\ResourceModel\ComfinoApplication as ApplicationResource;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;

class ComfinoStatusManagement implements ComfinoStatusManagementInterface
{
    public const CREATED_STATUS = 'CREATED';
    public const WAITING_FOR_FILLING_STATUS = 'WAITING_FOR_FILLING';
    public const WAITING_FOR_CONFIRMATION_STATUS = 'WAITING_FOR_CONFIRMATION';
    public const WAITING_FOR_PAYMENT_STATUS = 'WAITING_FOR_PAYMENT';
    public const ACCEPTED_STATUS = 'ACCEPTED';
    public const REJECTED_STATUS = 'REJECTED';
    public const CANCELLED_BY_SHOP_STATUS = 'CANCELLED_BY_SHOP';
    public const CANCELLED_STATUS = 'CANCELLED';
    public const PAID_STATUS = 'PAID';

    public const NEW_STATE = [
        self::CREATED_STATUS,
        self::WAITING_FOR_CONFIRMATION_STATUS,
        self::WAITING_FOR_FILLING_STATUS,
    ];

    public const REJECTED_STATE = [
        self::REJECTED_STATUS,
        self::CANCELLED_BY_SHOP_STATUS,
        self::CANCELLED_STATUS,
    ];

    public const COMPLETED_STATE = [
        self::ACCEPTED_STATUS,
        self::PAID_STATUS,
        self::WAITING_FOR_PAYMENT_STATUS,
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var ComfinoApplicationFactory
     */
    private $comfinoApplicationFactory;

    /**
     * @var ApplicationResource
     */
    private $applicationResource;

    /**
     * ComfinoStatusManagement constructor.
     *
     * @param LoggerInterface $logger
     * @param OrderRepository $orderRepository
     * @param ComfinoApplicationFactory $comfinoApplicationFactory
     * @param ApplicationResource $applicationResource
     */
    public function __construct(
        LoggerInterface $logger,
        OrderRepository $orderRepository,
        ComfinoApplicationFactory $comfinoApplicationFactory,
        ApplicationResource $applicationResource
    ) {
        $this->logger = $logger;
        $this->comfinoApplicationFactory = $comfinoApplicationFactory;
        $this->applicationResource = $applicationResource;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Change status for application and related order.
     *
     * @param int $applicationId
     * @param string $orderStatus
     *
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws InvalidExternalIdException
     * @throws NoSuchEntityException
     */
    public function changeApplicationAndOrderStatus(int $applicationId, string $orderStatus): void
    {
        $application = $this->getApplication($applicationId);

        if (!$application->getId()) {
            throw new InvalidExternalIdException(__('Invalid external ID!'));
        }

        $this->changeApplicationStatus($application, $orderStatus);
        $this->changeOrderStatus($application->getOrderId(), $orderStatus);
    }

    /**
     * Handle application save failure.
     *
     * @param OrderInterface $order
     *
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function applicationFailureStatus(OrderInterface $order): void
    {
        $status = Order::STATE_PENDING_PAYMENT;
        $order->setStatus($status)->setState($status);
        $order->addStatusToHistory(
            $order->getStatus(),
            __('Unsuccessful attempt to open the application. Communication error with Comfino API.')
        );
        $this->orderRepository->save($order);
    }

    /**
     * Change status for order and add status to history.
     *
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws InputException
     */
    protected function changeOrderStatus($orderId, $status): void
    {
        $order = $this->orderRepository->get($orderId);
        $origStatus = $order->getStatus();

        $this->setOrderStatus($order, $status);

        if ($origStatus !== Order::STATE_PROCESSING && $this->isCompletedStatus($status)) {
            $amount = $order->getBaseTotalDue();
            $payment = $order->getPayment();

            if ($payment !== null) {
                $payment->registerAuthorizationNotification($amount);
                $payment->registerCaptureNotification($amount);
            } else {
                $this->logger->warning("Comfino payment not found for order $orderId.", ['orderId' => $orderId]);
            }
        }

        $this->orderRepository->save($order);
    }

    /**
     * Get application by id.
     *
     * @param int $id
     *
     * @return ComfinoApplication
     */
    private function getApplication(int $id): ComfinoApplication
    {
        $application = $this->comfinoApplicationFactory->create();
        $this->applicationResource->load($application, $id, 'external_id');

        return $application;
    }

    /**
     * Change Comfino application status.
     *
     * @param ComfinoApplication $application
     * @param string $status
     *
     * @throws AlreadyExistsException
     */
    private function changeApplicationStatus(ComfinoApplication $application, string $status): void
    {
        $application->setStatus($status);
        $this->applicationResource->save($application);
    }

    /**
     * @param $status
     * @param $group
     *
     * @return bool
     */
    private function checkStatusGroup($status, $group): bool
    {
        return in_array($status, $group, true);
    }

    /**
     * @param OrderInterface $order
     * @param string $status
     */
    private function setOrderStatus(OrderInterface $order, string $status)
    {
        $newStatus = $this->mapStatus($status);
        $order->setStatus($newStatus)->setState($newStatus);
        $order->addStatusToHistory($newStatus, __('Comfino status: %1', $status));
    }

    /**
     * @param $status
     *
     * @return mixed|string
     */
    private function mapStatus($status)
    {
        if ($this->isCompletedStatus($status)) {
            return Order::STATE_PROCESSING;
        }
        if ($this->isNewStatus($status)) {
            return Order::STATE_PENDING_PAYMENT;
        }
        if ($this->isRejectedStatus($status)) {
            return Order::STATE_CANCELED;
        }

        return $status;
    }

    /**
     * @param $status
     *
     * @return bool
     */
    private function isRejectedStatus($status): bool
    {
        return $this->checkStatusGroup($status, ComfinoStatusManagement::REJECTED_STATE);
    }

    /**
     * @param $status
     *
     * @return bool
     */
    private function isNewStatus($status): bool
    {
        return $this->checkStatusGroup($status, ComfinoStatusManagement::NEW_STATE);
    }

    /**
     * @param $status
     *
     * @return bool
     */
    private function isCompletedStatus($status): bool
    {
        return $this->checkStatusGroup($status, ComfinoStatusManagement::COMPLETED_STATE);
    }
}
