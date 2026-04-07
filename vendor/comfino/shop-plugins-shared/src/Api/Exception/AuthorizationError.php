<?php

declare(strict_types=1);

namespace Comfino\Api\Exception;

use Comfino\Api\HttpErrorExceptionInterface;

class AuthorizationError extends \RuntimeException implements HttpErrorExceptionInterface
{
    private $url;

    private $requestBody;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, string $url = '', string $requestBody = '')
    {
        parent::__construct($message, $code, $previous);

        $this->url = $url;
        $this->requestBody = $requestBody;
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
        return '';
    }

    /**
     * @param string $responseBody
     */
    public function setResponseBody($responseBody): void
    {
    }

    public function getStatusCode(): int
    {
        return 401;
    }
}
