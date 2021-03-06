<?php

namespace Comfino\ComfinoGateway\Controller\Notification;

use Comfino\ComfinoGateway\Api\ComfinoStatusManagementInterface;
use Comfino\ComfinoGateway\Helper\Data;
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

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param RequestInterface $request
     * @param SerializerInterface $serializer
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     * @param ComfinoStatusManagementInterface $comfinoStatusManagement
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        SerializerInterface $serializer,
        JsonFactory $resultJsonFactory,
        Data $helper,
        ComfinoStatusManagementInterface $comfinoStatusManagement
    ) {
        $this->request = $request;
        $this->serializer = $serializer;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->statusManagement = $comfinoStatusManagement;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $jsonContent = $this->request->getContent();

        if (!$this->isValidSignature($jsonContent)) {
            return $this->setResponse(400, __('Failed comparison of CR-Signature and shop hash.'));
        }

        $content = $this->serializer->unserialize($jsonContent);

        if (!isset($content['externalId'])) {
            return $this->setResponse(400, __('Empty content.'));
        }

        $this->statusManagement->changeApplicationAndOrderStatus($content['externalId'], $content['status']);

        return $this->setResponse(200, 'OK');
    }

    /**
     * @param int $code
     * @param string $content
     *
     * @return Json
     */
    private function setResponse(int $code, string $content): Json
    {
        return $this->resultJsonFactory->create()->setHttpResponseCode($code)->setData(['status' => $content]);
    }

    /**
     * @param string $jsonData
     *
     * @return bool
     */
    private function isValidSignature(string $jsonData): bool
    {
        $crSignature = $this->request->getHeader('CR-Signature');
        $hash = hash('sha3-256', $this->helper->getApiKey().$jsonData);

        return $crSignature === $hash;
    }
}
