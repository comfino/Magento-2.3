<?php

namespace Comperia\ComperiaGateway\Controller\Notification;

use Comperia\ComperiaGateway\Connector\ApiConnector;
use Comperia\ComperiaGateway\Exception\InvalidExternalIdException;
use Comperia\ComperiaGateway\Model\ComperiaApplicationFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Index
 *
 * @package Comperia\ComperiaGateway\Controller\Notification
 */
class Index extends Action
{
    public const CREATED_STATUS = 'CREATED';
    public const WAITING_FOR_FILLING_STATUS = "WAITING_FOR_FILLING";
    public const WAITING_FOR_CONFIRMATION_STATUS = "WAITING_FOR_CONFIRMATION";
    public const WAITING_FOR_PAYMENT_STATUS = "WAITING_FOR_PAYMENT";
    public const ACCEPTED_STATUS = "ACCEPTED";
    public const REJECTED_STATUS = "REJECTED";
    public const CANCELLED_STATUS = "CANCELLED";
    public const PAID_STATUS = "PAID";

    public const NOTIFICATION_URL = 'comperia/notification';

    /**
     * @var array
     */
    private $newState = [
        self::CREATED_STATUS,
        self::WAITING_FOR_CONFIRMATION_STATUS,
        self::WAITING_FOR_FILLING_STATUS,
    ];

    /**
     * @var array
     */
    private $rejectedState = [
        self::REJECTED_STATUS,
        self::CANCELLED_STATUS,
    ];

    /**
     * @var array
     */
    private $completedState = [
        self::ACCEPTED_STATUS,
        self::PAID_STATUS,
        self::WAITING_FOR_PAYMENT_STATUS,
    ];

    /**
     * @var Context
     */
    private $context;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ComperiaApplicationFactory
     */
    private $comperiaApplicationFactory;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * Index constructor.
     *
     * @param Context                    $context
     * @param RequestInterface           $request
     * @param ScopeConfigInterface       $scopeConfig
     * @param ComperiaApplicationFactory $comperiaApplicationFactory
     * @param OrderRepository            $orderRepository
     */
    public function __construct(Context $context, RequestInterface $request, ScopeConfigInterface $scopeConfig, ComperiaApplicationFactory $comperiaApplicationFactory, OrderRepository $orderRepository)
    {
        $this->context = $context;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->comperiaApplicationFactory = $comperiaApplicationFactory;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * @throws InputException
     * @throws InvalidExternalIdException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $jsonContent = $this->request->getContent();
        $content = json_decode($jsonContent, true);

        if (isset($content['externalId'])) {
            $application = $this->getApplication($content['externalId'])
                ->getData();

            if (empty($application)) {
                throw new InvalidExternalIdException('Invalid external ID!');
            }

            if (!$this->isValidSignature($jsonContent)) {
                $this->getResponse()
                    ->setStatusCode(400)
                    ->setContent('Failed comparission of CR-Signature and shop hash.');

                return;
            }

            $orderStatus = $content['status'];
            $this->changeApplicationStatus($orderStatus, $content['externalId']);
            $this->changeOrderStatus($application[0], $orderStatus);
        } else {
            $this->getResponse()
                ->setStatusCode(404)
                ->setContent('Empty content.');
        }
    }

    /**
     * @param string $orderStatus
     * @param string $externalId
     */
    private function changeApplicationStatus(string $orderStatus, string $externalId): void
    {
        /** @var AbstractCollection $collection */
        $application = $this->getApplication($externalId);

        foreach ($application as $item) {
            $item->setStatus($orderStatus);
            $item->setUpdatedAt(date('Y-m-d H:i:s'));
        }

        $application->save();
    }

    /**
     * @param string $externalId
     *
     * @return AbstractCollection
     */
    private function getApplication(string $externalId): AbstractCollection
    {
        return $this->comperiaApplicationFactory->create()
            ->getCollection()
            ->addFieldToFilter('external_id', $externalId);
    }

    /**
     * @param string $jsonData
     *
     * @return bool
     */
    private function isValidSignature(string $jsonData): bool
    {
        $crSignature = $this->request->getHeader('CR-Signature');

        $apiKey = $this->scopeConfig->getValue(ApiConnector::API_KEY, ScopeInterface::SCOPE_STORE);

        return $crSignature === hash('sha3-256', $apiKey . $jsonData);
    }

    /**
     * @param array  $application
     * @param string $orderStatus
     *
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    private function changeOrderStatus(array $application, string $orderStatus): void
    {
        $order = $this->orderRepository->get($application['order_id']);
        $payment = $order->getPayment();

        if (in_array($orderStatus, $this->newState, true)) {
            $order->addStatusToHistory($order->getStatus(), 'Comfino status: ' . $orderStatus);
        }

        if (in_array($orderStatus, $this->rejectedState, true)) {
            $order->setStatus(Order::STATE_CANCELED)
                ->setState(Order::STATE_CANCELED)
                ->addStatusToHistory(Order::STATE_CANCELED, 'Comfino status: ' . $orderStatus);
        }

        if (in_array($orderStatus, $this->completedState, true)) {
            $order->addStatusToHistory($order->getStatus(), 'Comfino status: ' . $orderStatus);
            $amount = $order->getGrandTotal();

            $payment->registerAuthorizationNotification($amount);
            $payment->registerCaptureNotification($amount);
        }

        $order->save();
    }
}
