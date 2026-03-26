<?php

declare(strict_types=1);

namespace Comfino\Api\Exception;

use Comfino\Api\HttpErrorExceptionInterface;
use ComfinoExternal\Psr\Http\Message\ResponseInterface;

class RequestValidationError extends \LogicException implements HttpErrorExceptionInterface
{
    private $url;

    private $requestBody;

    private $responseBody;

    private $deserializedResponseBody;
    /**
     * @var \ComfinoExternal\Psr\Http\Message\ResponseInterface
     */
    private $response;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, string $url = '', string $requestBody = '', string $responseBody = '', $deserializedResponseBody = null, ?ResponseInterface $response = null)
    {
        parent::__construct($message, $code, $previous);

        $this->url = $url;
        $this->requestBody = $requestBody;
        $this->responseBody = $responseBody;
        $this->deserializedResponseBody = $deserializedResponseBody;
        $this->response = $response;
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

    /**
     * @return mixed[]|bool|float|int|string|null
     */
    public function getDeserializedResponseBody()
    {
        return $this->deserializedResponseBody;
    }

    /**
     * @param float|int|bool|mixed[]|string|null $deserializedResponseBody
     */
    public function setDeserializedResponseBody($deserializedResponseBody): void
    {
        $this->deserializedResponseBody = $deserializedResponseBody;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @param \ComfinoExternal\Psr\Http\Message\ResponseInterface $response
     */
    public function setResponse($response): void
    {
        $this->response = $response;
    }

    public function getStatusCode(): int
    {
        return 400;
    }
}
