<?php

declare(strict_types=1);

namespace Comfino\Common\Api\Response;

use Comfino\Api\Request;
use Comfino\Api\SerializerInterface;
use Comfino\Common\Exception\ConnectionTimeout;
use ComfinoExternal\Psr\Http\Message\ResponseInterface;

class ValidateOrder extends \Comfino\Api\Response\ValidateOrder
{
    public $isTimeout;

    public $connectAttemptIdx;

    public $connectionTimeout;

    public $transferTimeout;

    /**
     * @param Request $request
     * @param ResponseInterface $response
     * @param SerializerInterface $serializer
     * @param \Throwable|null $exception
     */
    public function __construct(Request $request, ResponseInterface $response, SerializerInterface $serializer, ?\Throwable $exception = null)
    {
        parent::__construct($request, $response, $serializer, $exception);

        $isTimeout = false;
        $connectAttemptIdx = 0;
        $connectionTimeout = 0;
        $transferTimeout = 0;

        if ($exception instanceof ConnectionTimeout) {
            $isTimeout = true;
            $connectAttemptIdx = $exception->getConnectAttemptIdx();
            $connectionTimeout = $exception->getConnectionTimeout();
            $transferTimeout = $exception->getTransferTimeout();
        }

        $this->isTimeout = $isTimeout;
        $this->connectAttemptIdx = $connectAttemptIdx;
        $this->connectionTimeout = $connectionTimeout;
        $this->transferTimeout = $transferTimeout;
    }
}
