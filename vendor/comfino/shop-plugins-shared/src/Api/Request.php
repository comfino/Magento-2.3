<?php

declare(strict_types=1);

namespace Comfino\Api;

use Comfino\Api\Exception\RequestValidationError;
use ComfinoExternal\Psr\Http\Message\RequestFactoryInterface;
use ComfinoExternal\Psr\Http\Message\RequestInterface;
use ComfinoExternal\Psr\Http\Message\StreamFactoryInterface;

abstract class Request
{
    protected $serializer;

    protected $method;

    protected $apiEndpointPath;

    protected $requestHeaders;

    protected $requestParams;

    protected $requestUri;

    protected $requestBody;

    /**
     * @param \Comfino\Api\SerializerInterface $serializer
     */
    final public function setSerializer($serializer): self
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface $streamFactory
     * @param string $apiHost
     * @param int $apiVersion
     * @return RequestInterface
     * @throws RequestValidationError
     */
    final public function getPsrRequest(
        $requestFactory,
        $streamFactory,
        $apiHost,
        $apiVersion
    ): RequestInterface {
        $this->requestUri = $this->getApiEndpointUri($apiHost, $apiVersion);

        if (empty($this->method)) {
            throw new RequestValidationError('Invalid request data: HTTP method undefined.', 0, null, $this->requestUri);
        }
        if (empty($this->apiEndpointPath)) {
            throw new RequestValidationError('Invalid request data: API endpoint path undefined.', 0, null, $this->requestUri);
        }

        $request = $requestFactory->createRequest($this->method, $this->requestUri);

        if (!empty($requestHeaders = $this->getRequestHeaders())) {
            foreach ($requestHeaders as $headerName => $headerValue) {
                $request = $request->withHeader($headerName, $headerValue);
            }
        }

        try {
            $this->requestBody = $this->serializeRequestBody();
        } catch (RequestValidationError $e) {
            $e->setUrl($this->requestUri);

            throw $e;
        }

        return $this->requestBody !== null ? $request->withBody($streamFactory->createStream($this->requestBody)) : $request;
    }

    /**
     * @return string|null
     */
    final public function getRequestUri(): ?string
    {
        return $this->requestUri;
    }

    /**
     * @return string|null
     */
    final public function getRequestBody(): ?string
    {
        return $this->requestBody;
    }

    /**
     * @return string
     * @throws RequestValidationError
     */
    public function __toString(): string
    {
        return ($serializedBody = $this->serializeRequestBody()) !== null ? $serializedBody : '';
    }

    /**
     * @param string $method
     * @return void
     */
    final protected function setRequestMethod($method): void
    {
        $this->method = strtoupper(trim($method));
    }

    /**
     * @param string $apiEndpointPath
     * @return void
     */
    final protected function setApiEndpointPath($apiEndpointPath): void
    {
        $this->apiEndpointPath = trim($apiEndpointPath, " /\n\r\t\v\0");
    }

    /**
     * @param string[] $requestHeaders
     * @return void
     */
    final protected function setRequestHeaders($requestHeaders): void
    {
        $this->requestHeaders = $requestHeaders;
    }

    /**
     * @param string[] $requestParams
     * @return void
     */
    final protected function setRequestParams($requestParams): void
    {
        $this->requestParams = array_map(
            static function ($requestParam): string {
                if (is_string($requestParam)) {
                    return $requestParam;
                }

                if ($requestParam === null) {
                    return '';
                }

                return (string) $requestParam;
            },
            $requestParams
        );
    }

    /**
     * @return string|null
     * @throws RequestValidationError
     */
    protected function serializeRequestBody(): ?string
    {
        return ($body = $this->prepareRequestBody()) !== null ? $this->serializer->serialize($body) : null;
    }

    /**
     * @param string $apiHost
     * @param int $apiVersion
     * @return string
     */
    protected function getApiEndpointUri($apiHost, $apiVersion): string
    {
        $uri = implode('/', [trim($apiHost, " /\n\r\t\v\0"), "v$apiVersion", $this->apiEndpointPath]);

        if (!empty($this->requestParams)) {
            $uri .= ('?' . http_build_query($this->requestParams));
        }

        return $uri;
    }

    final protected function getRequestHeaders(): ?array
    {
        return $this->requestHeaders;
    }

    /**
     * @return array|null
     */
    abstract protected function prepareRequestBody(): ?array;
}
