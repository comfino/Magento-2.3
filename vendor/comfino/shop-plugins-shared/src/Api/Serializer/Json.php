<?php

declare(strict_types=1);

namespace Comfino\Api\Serializer;

use Comfino\Api\Exception\RequestValidationError;
use Comfino\Api\Exception\ResponseValidationError;
use Comfino\Api\SerializerInterface;

class Json implements SerializerInterface
{
    /**
     * @param mixed $requestData
     */
    public function serialize($requestData): string
    {
        try {
            $serializedRequestBody = json_encode($requestData, JSON_PRESERVE_ZERO_FRACTION);
        } catch (\JsonException $e) {
            throw new RequestValidationError("Invalid request data: {$e->getMessage()}", 0, $e);
        }

        return $serializedRequestBody;
    }

    /**
     * @param string $responseBody
     * @return mixed
     */
    public function unserialize($responseBody)
    {
        try {
            $deserializedResponseBody = json_decode($responseBody, true, 512, 0);
        } catch (\JsonException $e) {
            throw new ResponseValidationError("Invalid response data: {$e->getMessage()}", 0, $e, '', '', $responseBody);
        }

        return $deserializedResponseBody;
    }
}
