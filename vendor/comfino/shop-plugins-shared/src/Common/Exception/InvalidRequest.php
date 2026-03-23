<?php

declare(strict_types=1);

namespace Comfino\Common\Exception;

use Comfino\Api\HttpErrorExceptionInterface;

class InvalidRequest extends \InvalidArgumentException implements HttpErrorExceptionInterface
{
    /**
     * @var string
     */
    private $url = '';
    /**
     * @var string
     */
    private $requestBody = '';
    public function __construct(string $url = '', string $requestBody = '', string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $this->url = $url;
        $this->requestBody = $requestBody;
        parent::__construct($message, $code, $previous);
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
        return 400;
    }
}
