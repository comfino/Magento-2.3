<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Serializer;

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
        if (($serializedRequestBody = json_encode($requestData, JSON_PRESERVE_ZERO_FRACTION)) === false && json_last_error() !== JSON_ERROR_NONE) {
            throw new RequestValidationError('Invalid request data: ' . json_last_error_msg(), json_last_error());
        }

        return $serializedRequestBody;
    }

    /**
     * @param string $responseBody
     * @return mixed
     */
    public function unserialize($responseBody)
    {
        if (($deserializedResponseBody = json_decode($responseBody, true)) === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new ResponseValidationError('Invalid response data: ' . json_last_error_msg(), json_last_error());
        }

        return $deserializedResponseBody;
    }
}
