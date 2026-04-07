<?php

namespace Comfino\ComfinoGateway\Controller\TransactionStatus;

use Comfino\Api\ApiService;
use Comfino\ComfinoGateway\Controller\AbstractApiEndpoint;
use Comfino\ErrorLogger;
use Magento\Framework\App\Action\HttpPutActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;

class Index extends AbstractApiEndpoint implements HttpPutActionInterface, CsrfAwareActionInterface
{
    public function execute(): ResultInterface
    {
        ErrorLogger::init();

        return $this->prepareResult(ApiService::processRequest('transactionStatus'));
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}