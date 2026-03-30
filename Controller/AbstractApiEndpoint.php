<?php

namespace Comfino\ComfinoGateway\Controller;

use ComfinoExternal\Psr\Http\Message\ResponseInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;

abstract class AbstractApiEndpoint
{
    protected RawFactory $resultRawFactory;

    public function __construct(RawFactory $resultRawFactory)
    {
        $this->resultRawFactory = $resultRawFactory;
    }

    protected function prepareResult(ResponseInterface $psr7Response): ResultInterface
    {
        $result = $this->resultRawFactory->create();
        $result->setHttpResponseCode($psr7Response->getStatusCode());

        foreach ($psr7Response->getHeaders() as $headerName => $headerValues) {
            foreach ($headerValues as $headerValue) {
                $result->setHeader($headerName, $headerValue);
            }
        }

        $body = $psr7Response->getBody()->getContents();
        $result->setContents(!empty($body) ? $body : $psr7Response->getReasonPhrase());

        return $result;
    }
}