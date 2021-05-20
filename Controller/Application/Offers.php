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
class Offers extends Action
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
        $response = $this->apiConnector->getOffers();

        return $responseJson->setData($response->getOffersData(), true);
    }
}
