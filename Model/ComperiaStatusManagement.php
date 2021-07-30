<?php

namespace Comperia\ComperiaGateway\Model;

use Comperia\ComperiaGateway\Api\ComperiaStatusManagementInterface;
use Comperia\ComperiaGateway\Exception\InvalidExternalIdException;
use Comperia\ComperiaGateway\Model\ResourceModel\ComperiaApplication as ApplicationResource;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class ComperiaStatusManagement implements ComperiaStatusManagementInterface
{
    public const CREATED_STATUS = 'CREATED';
    public const WAITING_FOR_FILLING_STATUS = "WAITING_FOR_FILLING";
    public const WAITING_FOR_CONFIRMATION_STATUS = "WAITING_FOR_CONFIRMATION";
    public const WAITING_FOR_PAYMENT_STATUS = "WAITING_FOR_PAYMENT";
    public const ACCEPTED_STATUS = "ACCEPTED";
    public const REJECTED_STATUS = "REJECTED";
    public const CANCELLED_STATUS = "CANCELLED";
    public const PAID_STATUS = "PAID";

    public const NEW_STATE = [
        self::CREATED_STATUS,
        self::WAITING_FOR_CONFIRMATION_STATUS,
        self::WAITING_FOR_FILLING_STATUS,
    ];

    public const REJECTED_STATE = [
        self::REJECTED_STATUS,
        self::CANCELLED_STATUS,
    ];

    public const COMPLETED_STATE = [
        self::ACCEPTED_STATUS,
        self::PAID_STATUS,
        self::WAITING_FOR_PAYMENT_STATUS,
    ];
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var ComperiaApplicationFactory
     */
    private $comperiaApplicationFactory;
    /**
     * @var ApplicationResource
     */
    private $applicationResource;

    /**
     * ComperiaStatusManagement constructor.
     * @param OrderRepository $orderRepository
     * @param ComperiaApplicationFactory $comperiaApplicationFactory
     * @param ApplicationResource $applicationResource
     */
    public function __construct(
        OrderRepository $orderRepository,
        ComperiaApplicationFactory $comperiaApplicationFactory,
        ApplicationResource $applicationResource
    ) {
        $this->comperiaApplicationFactory = $comperiaApplicationFactory;
        $this->applicationResource = $applicationResource;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Change status for application and related order
     * @param int $applicationId
     * @param string $orderStatus
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
     * Get application by id
     * @param int $id
     * @return ComperiaApplication
     */
    private function getApplication(int $id): ComperiaApplication
    {
        $application = $this->comperiaApplicationFactory->create();
        $this->applicationResource->load($application, $id, 'external_id');
        return $application;
    }

    /**
     * Change comperia application status
     * @param ComperiaApplication $application
     * @param string $status
     * @throws AlreadyExistsException
     */
    private function changeApplicationStatus(ComperiaApplication $application, string $status)
    {
        $application->setStatus($status);
        $this->applicationResource->save($application);
    }

    /**
     * Change Status for Order and add status to history
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws InputException
     */
    protected function changeOrderStatus($orderId, $status)
    {
        $order = $this->orderRepository->get($orderId);
        if (in_array($status, ComperiaStatusManagement::REJECTED_STATE, true)) {
            $order->setStatus(Order::STATE_CANCELED)->setState(Order::STATE_CANCELED);
        }
        $order->addStatusToHistory($order->getStatus(), __('Comfino status: %1', $status));
        if ($status == ComperiaStatusManagement::ACCEPTED_STATUS) {
            $amount = $order->getGrandTotal();
            $payment = $order->getPayment();
            $payment->registerAuthorizationNotification($amount);
            $payment->registerCaptureNotification($amount);
        }

        $this->orderRepository->save($order);
    }

    /**
     * Handle application save failure
     * @param OrderInterface $order
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
            __('Unsuccessful attempt to open the application. Communication error with Comperia API.')
        );
        $this->orderRepository->save($order);
    }
}
