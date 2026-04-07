<?php

declare(strict_types=1);

namespace Comfino\Api;

interface HttpErrorExceptionInterface
{
    public function getUrl(): string;

    public function getRequestBody(): string;

    /**
     * @param string $requestBody
     */
    public function setRequestBody($requestBody): void;

    public function getResponseBody(): string;

    /**
     * @param string $responseBody
     */
    public function setResponseBody($responseBody): void;

    public function getStatusCode(): int;
}
