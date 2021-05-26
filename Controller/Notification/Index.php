<?php

namespace Comperia\ComperiaGateway\Controller\Notification;

use Comperia\ComperiaGateway\Connector\ApiConnector;
use Comperia\ComperiaGateway\Exception\InvalidExternalIdException;
use Comperia\ComperiaGateway\Exception\InvalidSignatureException;
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
    const CREATED_STATUS = 'CREATED';
    const WAITING_FOR_FILLING_STATUS = "WAITING_FOR_FILLING";
    const WAITING_FOR_CONFIRMATION_STATUS = "WAITING_FOR_CONFIRMATION";
    const ACCEPTED_STATUS = "ACCEPTED";
    const REJECTED_STATUS = "REJECTED";
    const CANCELLED_STATUS = "CANCELLED";
    const PAID_STATUS = "PAID";

    const NOTIFICATION_URL = 'comperia/notification';

    /**
     * @var array
     */
    private $newState = [
        self::STATUS_CREATED,
        self::STATUS_WAITING_FOR_CONFIRMATION,
        self::STATUS_WAITING_FOR_FILLING,
    ];
    /**
     * @var array
     */
    private $rejectedState = [
        self::STATUS_CANCELLED,
        self::STATUS_CANCELLED_BY_SHOP,
    ];
    /**
     * @var array
     */
    private $completedState = [
        self::STATUS_ACCEPTED,
        self::STATUS_PAID,
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
    public function __construct(
        Context $context,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        ComperiaApplicationFactory $comperiaApplicationFactory,
        OrderRepository $orderRepository
    ) {
        $this->context = $context;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->comperiaApplicationFactory = $comperiaApplicationFactory;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * @throws InvalidExternalIdException
     * @throws InvalidSignatureException
     */
    public function execute()
    {
        $jsonContent = $this->request->getContent();
        $content = json_decode($jsonContent, true);
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
     * @return bool
     */
    private function isValidSignature(string $jsonData): bool
    {
        $crSignature = $this->request->getHeader('CR-Signature');

        $apiKey = $this->scopeConfig->getValue(ApiConnector::API_KEY, ScopeInterface::SCOPE_STORE);

        return $crSignature === hash('sha3-256' , $apiKey . $jsonData);
    }

    /**
     * @param array  $application
     * @param string $orderStatus
     *
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function changeOrderStatus(array $application, string $orderStatus): void
    {
        $order = $this->orderRepository->get($application['order_id']);

        switch ($orderStatus) {
            case self::CREATED_STATUS:
            case self::WAITING_FOR_FILLING_STATUS:
            case self::WAITING_FOR_CONFIRMATION_STATUS:
        }

        if (in_array($orderStatus, $this->newState)) {
            $order->setStatus(Order::STATE_NEW)
                  ->setState(Order::STATE_NEW);
        }

        if (in_array($orderStatus, $this->rejectedState)) {
            $order->setStatus(Order::STATE_CANCELED)
                  ->setState(Order::STATE_CANCELED);
        }

        if (in_array($orderStatus, $this->completedState)) {
            $order->setStatus(Order::STATE_COMPLETE)
                  ->setState(Order::STATE_COMPLETE);
        }

        $order->save();
    }
}
