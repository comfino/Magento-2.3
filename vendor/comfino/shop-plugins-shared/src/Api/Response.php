<?php

declare(strict_types=1);

namespace Comfino\Api;

use Comfino\Api\Exception\AccessDenied;
use Comfino\Api\Exception\AuthorizationError;
use Comfino\Api\Exception\Conflict;
use Comfino\Api\Exception\Forbidden;
use Comfino\Api\Exception\MethodNotAllowed;
use Comfino\Api\Exception\NotFound;
use Comfino\Api\Exception\RequestValidationError;
use Comfino\Api\Exception\ResponseValidationError;
use Comfino\Api\Exception\ServiceUnavailable;

abstract class Response
{
    protected $request;

    protected $response;

    protected $serializer;

    protected $exception;

    protected $headers = [];

    /**
     * @return string[]
     */
    final public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $headerName
     * @return bool
     */
    final public function hasHeader($headerName): bool
    {
        if (isset($this->headers[$headerName])) {
            return true;
        }

        foreach ($this->headers as $responseHeaderName => $headerValue) {
            if (strcasecmp($responseHeaderName, $headerName) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $headerName
     * @param string|null $defaultValue
     * @return string|null
     */
    final public function getHeader($headerName, $defaultValue = null): ?string
    {
        if (isset($this->headers[$headerName])) {
            return $this->headers[$headerName];
        }

        foreach ($this->headers as $responseHeaderName => $headerValue) {
            if (strcasecmp($responseHeaderName, $headerName) === 0) {
                return $headerValue;
            }
        }

        return $defaultValue;
    }

    /**
     * @return Response
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     */
    final protected function initFromPsrResponse(): self
    {
        if ($this->response === null) {
            return $this;
        }

        $requestBody = ($this->request->getRequestBody() ?? '');

        $this->response->getBody()->rewind();
        $responseBody = $this->response->getBody()->getContents();

        $this->headers = [];

        foreach ($this->response->getHeaders() as $headerName => $headerValues) {
            $this->headers[$headerName] = end($headerValues);
        }

        if ($this->exception !== null) {
            return $this;
        }

        if ($this->response->hasHeader('Content-Type') && strpos($this->response->getHeader('Content-Type')[0], 'application/json') !== false) {
            try {
                $deserializedResponseBody = $this->deserializeResponseBody($responseBody, $this->serializer);
            } catch (ResponseValidationError $e) {
                $e->setUrl($this->request->getRequestUri());
                $e->setRequestBody($requestBody);
                $e->setResponseBody($responseBody);

                throw $e;
            }
        } else {
            $deserializedResponseBody = $responseBody;
        }

        if ($this->exception === null && $this->response->getStatusCode() >= 500) {
            throw new ServiceUnavailable(
                "Comfino API service is unavailable: {$this->response->getReasonPhrase()} [{$this->response->getStatusCode()}]",
                $this->response->getStatusCode(),
                null,
                $this->request->getRequestUri(),
                $requestBody,
                $responseBody
            );
        }

        if ($this->exception === null && $this->response->getStatusCode() >= 400) {
            switch ($this->response->getStatusCode()) {
                case 400:
                    throw new RequestValidationError(
                        $this->getErrorMessage(
                            $this->response->getStatusCode(),
                            $deserializedResponseBody,
                            "Invalid request data: {$this->response->getReasonPhrase()} [{$this->response->getStatusCode()}]"
                        ),
                        $this->response->getStatusCode(),
                        null,
                        $this->request->getRequestUri(),
                        $requestBody,
                        $responseBody,
                        $deserializedResponseBody,
                        $this->response
                    );

                case 401:
                    throw new AuthorizationError(
                        $this->getErrorMessage($this->response->getStatusCode(), $deserializedResponseBody, "Invalid credentials: {$this->response->getReasonPhrase()} [{$this->response->getStatusCode()}]"),
                        $this->response->getStatusCode(),
                        null,
                        $this->request->getRequestUri(),
                        $requestBody
                    );

                case 403:
                    throw new Forbidden(
                        $this->getErrorMessage(
                            $this->response->getStatusCode(),
                            $deserializedResponseBody,
                            "Access denied: {$this->response->getReasonPhrase()} [{$this->response->getStatusCode()}]"
                        ),
                        $this->response->getStatusCode(),
                        null,
                        $this->request->getRequestUri(),
                        $requestBody,
                        $responseBody
                    );

                case 404:
                    throw new NotFound(
                        $this->getErrorMessage(
                            $this->response->getStatusCode(),
                            $deserializedResponseBody,
                            "Entity not found: {$this->response->getReasonPhrase()} [{$this->response->getStatusCode()}]"
                        ),
                        $this->response->getStatusCode(),
                        null,
                        $this->request->getRequestUri(),
                        $requestBody,
                        $responseBody
                    );

                case 405:
                    throw new MethodNotAllowed(
                        $this->getErrorMessage(
                            $this->response->getStatusCode(),
                            $deserializedResponseBody,
                            "Method not allowed: {$this->response->getReasonPhrase()} [{$this->response->getStatusCode()}]"
                        ),
                        $this->response->getStatusCode(),
                        null,
                        $this->request->getRequestUri(),
                        $requestBody,
                        $responseBody
                    );

                case 409:
                    throw new Conflict(
                        $this->getErrorMessage(
                            $this->response->getStatusCode(),
                            $deserializedResponseBody,
                            "Entity already exists: {$this->response->getReasonPhrase()} [{$this->response->getStatusCode()}]"
                        ),
                        $this->response->getStatusCode(),
                        null,
                        $this->request->getRequestUri(),
                        $requestBody,
                        $responseBody
                    );

                default:
                    throw new RequestValidationError(
                        "Invalid request data: {$this->response->getReasonPhrase()} [{$this->response->getStatusCode()}]",
                        $this->response->getStatusCode(),
                        null,
                        $this->request->getRequestUri(),
                        $requestBody,
                        $responseBody,
                        $deserializedResponseBody,
                        $this->response
                    );
            }
        }

        if (($errorMessage = $this->getErrorMessage($this->response->getStatusCode(), $deserializedResponseBody)) !== null) {
            throw new RequestValidationError(
                $errorMessage,
                $this->response->getStatusCode(),
                null,
                $this->request->getRequestUri(),
                $requestBody,
                $responseBody,
                $deserializedResponseBody,
                $this->response
            );
        }

        try {
            $this->processResponseBody($deserializedResponseBody);
        } catch (ResponseValidationError $e) {
            $e->setUrl($this->request->getRequestUri());
            $e->setRequestBody($requestBody);
            $e->setResponseBody($responseBody);

            throw $e;
        }

        return $this;
    }

    /**
     * @throws ResponseValidationError
     * @param mixed[]|string|bool|null $deserializedResponseBody
     */
    abstract protected function processResponseBody($deserializedResponseBody): void;

    /**
     * @throws ResponseValidationError
     * @return mixed[]|bool|string|null
     */
    private function deserializeResponseBody(string $responseBody, SerializerInterface $serializer)
    {
        return !empty($responseBody) ? $serializer->unserialize($responseBody) : null;
    }

    /**
     * @param mixed[]|string|bool|null $deserializedResponseBody
     */
    private function getErrorMessage(int $statusCode, $deserializedResponseBody, ?string $defaultMessage = null): ?string
    {
        if (!is_array($deserializedResponseBody)) {
            return $defaultMessage;
        }

        $errorMessages = [];

        if (isset($deserializedResponseBody['errors'])) {
            $errorMessages = array_map(
                static function (string $errorFieldName, string $errorMessage) {
                    return "$errorFieldName: $errorMessage";
                },
                array_keys($deserializedResponseBody['errors']),
                array_values($deserializedResponseBody['errors'])
            );
        } elseif (isset($deserializedResponseBody['message'])) {
            $errorMessages = [$deserializedResponseBody['message']];
        } elseif ($statusCode >= 400) {
            foreach ($deserializedResponseBody as $errorFieldName => $errorMessage) {
                $errorMessages[] = "$errorFieldName: $errorMessage";
            }
        }

        return count($errorMessages) ? implode("\n", $errorMessages) : $defaultMessage;
    }
}
