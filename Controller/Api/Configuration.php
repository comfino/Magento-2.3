<?php

namespace Comfino\ComfinoGateway\Controller\Api;

use Comfino\Api\ApiService;
use Comfino\ErrorLogger;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;

class Configuration extends AbstractApiEndpoint implements HttpGetActionInterface, HttpPostActionInterface
{
    public function execute(): ResultInterface
    {
        ErrorLogger::init();

        return $this->prepareResult(ApiService::processRequest('configuration'));
    }
}
