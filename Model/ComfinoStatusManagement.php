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
    public const COMFINO_CREATED = 'COMFINO_CREATED';
    public const COMFINO_WAITING_FOR_FILLING = 'COMFINO_WAITING_FOR_FILLING';
    public const COMFINO_WAITING_FOR_CONFIRMATION = 'COMFINO_WAITING_FOR_CONFIRMATION';
    public const COMFINO_WAITING_FOR_PAYMENT = 'COMFINO_WAITING_FOR_PAYMENT';
    public const COMFINO_ACCEPTED = 'COMFINO_ACCEPTED';
    public const COMFINO_PAID = 'COMFINO_PAID';
    public const COMFINO_REJECTED = 'COMFINO_REJECTED';
    public const COMFINO_RESIGN = 'COMFINO_RESIGN';
    public const COMFINO_CANCELLED_BY_SHOP = 'COMFINO_CANCELLED_BY_SHOP';
    public const COMFINO_CANCELLED = 'COMFINO_CANCELLED';

    public const CREATED = 'CREATED';
    public const WAITING_FOR_FILLING = 'WAITING_FOR_FILLING';
    public const WAITING_FOR_CONFIRMATION = 'WAITING_FOR_CONFIRMATION';
    public const WAITING_FOR_PAYMENT = 'WAITING_FOR_PAYMENT';
    public const ACCEPTED = 'ACCEPTED';
    public const PAID = 'PAID';
    public const REJECTED = 'REJECTED';
    public const RESIGN = 'RESIGN';
    public const CANCELLED_BY_SHOP = 'CANCELLED_BY_SHOP';
    public const CANCELLED = 'CANCELLED';

    public const CUSTOM_ORDER_STATUSES = [
        ComfinoStatusManagement::COMFINO_CREATED => [
            'name' => 'Order created - waiting for payment (Comfino)',
            'name_pl' => 'Zamówienie utworzone - oczekiwanie na płatność (Comfino)',
        ],
        ComfinoStatusManagement::COMFINO_ACCEPTED => [
            'name' => 'Credit granted (Comfino)',
            'name_pl' => 'Kredyt udzielony (Comfino)',
        ],
        ComfinoStatusManagement::COMFINO_REJECTED => [
            'name' => 'Credit rejected (Comfino)',
            'name_pl' => 'Wniosek kredytowy odrzucony (Comfino)',
        ],
        ComfinoStatusManagement::COMFINO_RESIGN => [
            'name' => 'Resigned (Comfino)',
            'name_pl' => 'Odstąpiono (Comfino)',
        ],
        ComfinoStatusManagement::COMFINO_CANCELLED_BY_SHOP => [
            'name' => 'Cancelled by shop (Comfino)',
            'name_pl' => 'Anulowano przez sklep (Comfino)',
        ],
        ComfinoStatusManagement::COMFINO_CANCELLED => [
            'name' => 'Cancelled (Comfino)',
            'name_pl' => 'Anulowano (Comfino)',
        ],
    ];

    private const STATUSES = [
        self::CREATED => self::COMFINO_CREATED,
        self::WAITING_FOR_FILLING => self::COMFINO_WAITING_FOR_FILLING,
        self::WAITING_FOR_CONFIRMATION => self::COMFINO_WAITING_FOR_CONFIRMATION,
        self::WAITING_FOR_PAYMENT => self::COMFINO_WAITING_FOR_PAYMENT,
        self::ACCEPTED => self::COMFINO_ACCEPTED,
        self::REJECTED => self::COMFINO_REJECTED,
        self::PAID => self::COMFINO_PAID,
        self::RESIGN => self::COMFINO_RESIGN,
        self::CANCELLED_BY_SHOP => self::COMFINO_CANCELLED_BY_SHOP,
        self::CANCELLED => self::COMFINO_CANCELLED,
    ];

    /**
     * After setting notification status we want some statuses to change to internal Magento statuses right away.
     */
    private const CHANGE_STATUS_MAP = [
        self::CREATED => self::COMFINO_CREATED,
        self::ACCEPTED => Order::STATE_PROCESSING,
        self::CANCELLED => Order::STATE_CANCELED,
        self::CANCELLED_BY_SHOP => Order::STATE_CANCELED,
        self::REJECTED => Order::STATE_CANCELED,
        self::RESIGN => Order::STATE_CANCELED,
    ];

    private const IGNORED_STATUSES = [
        self::WAITING_FOR_FILLING,
        self::WAITING_FOR_CONFIRMATION,
        self::WAITING_FOR_PAYMENT,
        self::PAID,
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
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws InvalidExternalIdException
     * @throws NoSuchEntityException
     */
    public function changeApplicationAndOrderStatus(int $applicationId, string $orderStatus): bool
    {
        if (in_array($orderStatus, self::IGNORED_STATUSES, true)) {
            return true;
        }

        $application = $this->getApplication($applicationId);

        if (!$application->getId()) {
            throw new InvalidExternalIdException(__('Invalid external ID!'));
        }

        $status = $this->getStatus($orderStatus);

        if ($status === Order::STATE_HOLDED) {
            return false;
        }

        $this->changeApplicationStatus($application, $orderStatus);
        $this->changeOrderStatus($application->getOrderId(), $orderStatus);

        return true;
    }

    /**
     * Handle application save failure.
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
        $currentStatus = $order->getStatus();

        $this->setOrderStatus($order, $status);

        if ($currentStatus !== Order::STATE_PROCESSING && $status === self::ACCEPTED) {
            $amount = $order->getBaseTotalDue();
            $payment = $order->getPayment();

            if ($payment !== null && $amount > 0) {
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
     * @throws AlreadyExistsException
     */
    private function changeApplicationStatus(ComfinoApplication $application, string $status): void
    {
        $application->setStatus($status);
        $this->applicationResource->save($application);
    }

    private function setOrderStatus(OrderInterface $order, string $status): void
    {
        $newStatus = $this->mapStatus($status);
        $statusDesc = $this->getStatusDescription($status);
        $statusNote = __('Comfino status: %1', $status) . (!empty($statusDesc) ? ' [' . $statusDesc . ']' : '');

        $order->setStatus($newStatus)->setState($newStatus);
        $order->addStatusToHistory($newStatus, $statusNote);
    }

    private function getStatusDescription(string $status): string
    {
        $comfinoStatus = $this->getStatus($status);

        if (array_key_exists($comfinoStatus, self::CUSTOM_ORDER_STATUSES)) {
            return __(self::CUSTOM_ORDER_STATUSES[$comfinoStatus]['name']);
        }

        return '';
    }

    private function getStatus(string $status): string
    {
        $status = strtoupper($status);

        if (array_key_exists($status, self::STATUSES)) {
            return self::STATUSES[$status];
        }

        return Order::STATE_HOLDED;
    }

    private function mapStatus(string $status): string
    {
        $status = strtoupper($status);

        if (array_key_exists($status, self::CHANGE_STATUS_MAP)) {
            return strtolower(self::CHANGE_STATUS_MAP[$status]);
        }

        return Order::STATE_HOLDED;
    }
}
