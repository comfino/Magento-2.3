<?php

namespace Comperia\ComperiaGateway\Controller\Application;

use Comperia\ComperiaGateway\Connector\ApiConnector;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
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
     * @var ApiConnector
     */
    private $apiConnector;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param ApiConnector $apiConnector
     * @param JsonFactory $jsonFactory
     */
    public function __construct(Context $context, ApiConnector $apiConnector, JsonFactory $jsonFactory)
    {
        $this->apiConnector = $apiConnector;
        $this->jsonFactory = $jsonFactory;

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
