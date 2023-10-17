<?php

namespace Comfino\ComfinoGateway\Controller\Notification;

use Comfino\ComfinoGateway\Api\ComfinoStatusManagementInterface;
use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Model\ComfinoStatusManagement;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Index extends Action
{
    public const NOTIFICATION_URL = 'comfino/notification';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ComfinoStatusManagementInterface
     */
    private $statusManagement;

    public function __construct(Context $context, RequestInterface $request, SerializerInterface $serializer, JsonFactory $resultJsonFactory, Data $helper, ComfinoStatusManagementInterface $comfinoStatusManagement)
    {
        parent::__construct($context);

        $this->request = $request;
        $this->serializer = $serializer;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->statusManagement = $comfinoStatusManagement;
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $jsonContent = $this->request->getContent();

        if (!$this->helper->isValidSignature($this->getSignature(), $jsonContent)) {
            return $this->setResponse(400, __('Failed comparison of CR-Signature and shop hash.'));
        }

        $data = $this->serializer->unserialize($jsonContent);

        if (!isset($data['externalId'])) {
            return $this->setResponse(400, 'External ID must be set.');
        }

        if (!isset($data['status'])) {
            return $this->setResponse(400, 'Status must be set.');
        }

        if ($data['status'] === ComfinoStatusManagement::CANCELLED_BY_SHOP) {
            return $this->setResponse(400, 'Invalid status ' . ComfinoStatusManagement::CANCELLED_BY_SHOP . '.');
        }

        if (!$this->statusManagement->changeApplicationAndOrderStatus($data['externalId'], $data['status'])) {
            return $this->setResponse(400, sprintf('Invalid status %s.', $data['status']));
        }

        return $this->setResponse(200, 'OK');
    }

    private function getSignature(): string
    {
        $crSignature = $this->request->getHeader('CR-Signature');

        if (!empty($crSignature)) {
            return $crSignature;
        }

        $crSignature = $this->request->getHeader('X-CR-Signature');

        return !empty($crSignature) ? $crSignature : '';
    }

    private function setResponse(int $code, string $content): Json
    {
        return $this->resultJsonFactory->create()->setHttpResponseCode($code)->setData(['status' => $content]);
    }
}
