<?php

declare(strict_types=1);

namespace Comfino\Api\Exception;

use Comfino\Api\SensitiveDataRedactor;

trait SensitiveHttpExceptionTrait
{
    public function getRedactedRequestBody(): string
    {
        return SensitiveDataRedactor::redactJsonString($this->getRequestBody());
    }

    public function getRedactedResponseBody(): string
    {
        return SensitiveDataRedactor::redactJsonString($this->getResponseBody());
    }

    /**
     * @return array<string,
     */
    public function __debugInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'url' => $this->getUrl(),
            'statusCode' => $this->getStatusCode(),
            'requestBody' => $this->getRedactedRequestBody(),
            'responseBody' => $this->getRedactedResponseBody(),
        ];
    }
}
