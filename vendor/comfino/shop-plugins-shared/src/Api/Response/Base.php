<?php

declare(strict_types=1);

namespace Comfino\Api\Response;

use Comfino\Api\Exception\AccessDenied;
use Comfino\Api\Exception\AuthorizationError;
use Comfino\Api\Exception\RequestValidationError;
use Comfino\Api\Exception\ResponseValidationError;
use Comfino\Api\Exception\ServiceUnavailable;
use Comfino\Api\Request;
use Comfino\Api\Response;
use Comfino\Api\SerializerInterface;
use ComfinoExternal\Psr\Http\Message\ResponseInterface;

class Base extends Response
{
    /**
     * @param Request $request
     * @param ResponseInterface|null $response
     * @param SerializerInterface $serializer
     * @param \Throwable|null $exception
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     */
    public function __construct(Request $request, ?ResponseInterface $response, SerializerInterface $serializer, ?\Throwable $exception = null)
    {
        $this->request = $request;
        $this->response = $response;
        $this->serializer = $serializer;
        $this->exception = $exception;

        $this->initFromPsrResponse();
    }

    /**
     * @param mixed[]|string|int|float|bool|null $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
    }

    /**
     * @param array|string|bool|null $deserializedResponseBody
     * @param string $expectedType
     * @param string|null $fieldName
     * @return void
     * @throws ResponseValidationError
     */
    protected function checkResponseType($deserializedResponseBody, $expectedType, $fieldName = null): void
    {
        if (gettype($deserializedResponseBody) !== $expectedType) {
            if ($expectedType === 'double' && is_int($deserializedResponseBody)) {
                return;
            }

            if ($fieldName !== null) {
                throw new ResponseValidationError("Invalid response field \"$fieldName\" data type: $expectedType expected.");
            }

            throw new ResponseValidationError("Invalid response data type: $expectedType expected.");
        }
    }

    /**
     * @param array $deserializedResponseBody
     * @param string[] $expectedKeys
     * @return void
     * @throws ResponseValidationError
     */
    protected function checkResponseStructure($deserializedResponseBody, $expectedKeys): void
    {
        if (count($responseKeysDiff = array_diff($expectedKeys, array_keys($deserializedResponseBody))) > 0) {
            throw new ResponseValidationError('Invalid response data structure: missing fields: ' . implode(', ', $responseKeysDiff));
        }
    }
}
