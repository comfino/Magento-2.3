<?php

declare(strict_types=1);

namespace Comfino\Common\Exception;

use Comfino\Api\HttpErrorExceptionInterface;

class ConnectionTimeout extends \RuntimeException implements HttpErrorExceptionInterface
{
    private $connectAttemptIdx;

    private $connectionTimeout;

    private $transferTimeout;

    private $url;

    private $requestBody;

    private $responseBody;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        int $connectAttemptIdx = 1,
        int $connectionTimeout = 1,
        int $transferTimeout = 3,
        string $url = '',
        string $requestBody = '',
        string $responseBody = ''
    ) {
        parent::__construct($message, $code, $previous);

        $this->connectAttemptIdx = $connectAttemptIdx;
        $this->connectionTimeout = $connectionTimeout;
        $this->transferTimeout = $transferTimeout;
        $this->url = $url;
        $this->requestBody = $requestBody;
        $this->responseBody = $responseBody;
    }

    public function getConnectAttemptIdx(): int
    {
        return $this->connectAttemptIdx;
    }

    public function getConnectionTimeout(): int
    {
        return $this->connectionTimeout;
    }

    public function getTransferTimeout(): int
    {
        return $this->transferTimeout;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }

    public function getRequestBody(): string
    {
        return $this->requestBody;
    }

    /**
     * @param string $requestBody
     */
    public function setRequestBody($requestBody): void
    {
        $this->requestBody = $requestBody;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    /**
     * @param string $responseBody
     */
    public function setResponseBody($responseBody): void
    {
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): int
    {
        return 504;
    }
}
