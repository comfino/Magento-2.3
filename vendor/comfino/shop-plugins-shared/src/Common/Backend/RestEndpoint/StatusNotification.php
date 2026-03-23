<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\RestEndpoint;

use Comfino\Api\HttpErrorExceptionInterface;
use Comfino\Common\Backend\RestEndpoint;
use Comfino\Common\Exception\InvalidRequest;
use Comfino\Common\Shop\Order\StatusManager;
use ComfinoExternal\Psr\Http\Message\ServerRequestInterface;

class StatusNotification extends RestEndpoint
{
    /**
     * @var \Comfino\Common\Shop\Order\StatusManager
     */
    private $statusManager;
    /**
     * @var mixed[]
     */
    private $forbiddenStatuses;
    /**
     * @var mixed[]
     */
    private $ignoredStatuses;
    public function __construct(
        string $name,
        string $endpointUrl,
        StatusManager $statusManager,
        array $forbiddenStatuses,
        array $ignoredStatuses
    ) {
        $this->statusManager = $statusManager;
        $this->forbiddenStatuses = $forbiddenStatuses;
        $this->ignoredStatuses = $ignoredStatuses;
        parent::__construct($name, $endpointUrl);

        $this->methods = ['POST', 'PUT', 'PATCH'];
    }

    /**
     * @param ServerRequestInterface $serverRequest
     * @param string|null $endpointName
     */
    public function processRequest($serverRequest, $endpointName = null): ?array
    {
        $requestPayload = parent::processRequest($serverRequest, $endpointName);

        if (!isset($requestPayload['status'])) {
            throw new InvalidRequest(
                (string) $serverRequest->getUri(),
                $serverRequest->getBody()->getContents(),
                'Status must be set.'
            );
        }

        if (in_array($requestPayload['status'], $this->ignoredStatuses, true)) {
            return null;
        }

        if (!isset($requestPayload['externalId'])) {
            throw new InvalidRequest(
                (string) $serverRequest->getUri(),
                $serverRequest->getBody()->getContents(),
                'External ID must be set.'
            );
        }

        if (in_array($requestPayload['status'], $this->forbiddenStatuses, true)) {
            throw new InvalidRequest(
                (string) $serverRequest->getUri(),
                $serverRequest->getBody()->getContents(),
                'Invalid status "' . $requestPayload['status'] . '".'
            );
        }

        try {
            $this->statusManager->setOrderStatus($requestPayload['externalId'], $requestPayload['status']);
        } catch (\Throwable $e) {
            if ($e instanceof HttpErrorExceptionInterface) {
                throw $e;
            }

            throw new InvalidRequest(
                (string) $serverRequest->getUri(),
                $serverRequest->getBody()->getContents(),
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return null;
    }
}
