<?php

namespace Comfino\ComfinoGateway\Controller\Api;

use Comfino\Api\ApiService;
use Comfino\ErrorLogger;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;

class CacheInvalidate extends AbstractApiEndpoint implements HttpPostActionInterface
{
    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        ErrorLogger::init();

        return $this->prepareResult(ApiService::processRequest('cacheInvalidate'));
    }
}
