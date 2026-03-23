<?php

declare(strict_types=1);

namespace Comfino\Api;

use Comfino\Api\Exception\RequestValidationError;
use Comfino\Api\Exception\ResponseValidationError;

interface SerializerInterface
{
    /**
     * @param mixed $requestData
     * @return string
     * @throws RequestValidationError
     */
    public function serialize($requestData): string;

    /**
     * @param string $responseBody
     * @return mixed
     * @throws ResponseValidationError
     */
    public function unserialize($responseBody);
}
