<?php

namespace Comfino\ComfinoGateway\Controller\CacheInvalidate;

use Comfino\Api\ApiService;
use Comfino\ComfinoGateway\Controller\AbstractApiEndpoint;
use Comfino\ErrorLogger;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;

class Index extends AbstractApiEndpoint implements HttpPostActionInterface
{
    public function execute(): ResultInterface
    {
        ErrorLogger::init();

        return $this->prepareResult(ApiService::processRequest('cacheInvalidate'));
    }
}