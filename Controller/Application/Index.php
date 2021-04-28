<?php

namespace Comperia\ComperiaGateway\Controller\Application;

use Comperia\ComperiaGateway\Connector\ApiConnector;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Comperia\ComperiaGateway\Connector\Transaction\Response\ApplicationResponse;
use Comperia\ComperiaGateway\Model\ComperiaApplicationFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultInterface;
use Exception;
/**
 * Class Index
 *
 * @package Comperia\ComperiaGateway\Controller\Application
 */
class Index extends Action
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var ApiConnector
     */
    private $apiConnector;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ComperiaApplicationFactory
     */
    private $comperiaApplicationFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Index constructor.
     *
     * @param Context                    $context
     * @param ApiConnector               $apiConnector
     * @param JsonFactory                $jsonFactory
     * @param ComperiaApplicationFactory $comperiaApplicationFactory
     * @param Session                    $checkoutSession
     * @param LoggerInterface            $logger
     */
    public function __construct(
        Context $context,
        ApiConnector $apiConnector,
        JsonFactory $jsonFactory,
        ComperiaApplicationFactory $comperiaApplicationFactory,
        Session $checkoutSession,
        LoggerInterface $logger
    ) {
        $this->context = $context;
        $this->apiConnector = $apiConnector;
        $this->jsonFactory = $jsonFactory;
        $this->comperiaApplicationFactory = $comperiaApplicationFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     * @throws Exception
     */
    public function execute()
    {
        $responseJson = $this->jsonFactory->create();
        /** @var ApplicationResponse $response */
        $response = $this->apiConnector->applicationTransaction();
        if (!$response->isSuccessfull()) {
            $this->logger->emergency(
                "Unsuccessful attempt to open the application. Communication error with Comperia API.",
                [
                    'body' => $response->getBody(),
                    'code' => $response->getCode(),
                ]
            );

            return $responseJson->setData(['error' => __('Unsuccessful attempt to open the application. Please try again later.')]);
        }

        $this->saveApplication($response);

        return $responseJson->setData(['redirectUrl' => $response->getRedirectUri()]);
    }

    /**
     * @param ApplicationResponse $response
     *
     * @throws Exception
     */
    private function saveApplication(ApplicationResponse $response): void
    {
        $order = $this->checkoutSession->getLastRealOrder();

        $data = [
            'status'       => $response->getStatus(),
            'external_id'  => $response->getExternalId(),
            'created_at'   => date('Y-m-d H:i:s'),
            'redirect_uri' => $response->getRedirectUri(),
            'href'         => $response->getHref(),
            'order_id'     => $order->getEntityId(),
        ];

        $comperiaApplicationFactory = $this->comperiaApplicationFactory->create();
        $comperiaApplicationFactory->addData($data);
        $comperiaApplicationFactory->save();
    }
}
