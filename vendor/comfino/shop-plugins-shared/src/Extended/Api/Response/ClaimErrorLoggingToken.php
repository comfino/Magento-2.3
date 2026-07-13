<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Response;

use Comfino\Api\Response\Base;

class ClaimErrorLoggingToken extends Base
{
    /**
     * @var string
     */
    public $accessToken;

    /**
     * @var string
     */
    public $expiresAt;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'array');
        $this->checkResponseStructure($deserializedResponseBody, ['access_token', 'expires_at']);
        $this->checkResponseType($deserializedResponseBody['access_token'], 'string', 'access_token');
        $this->checkResponseType($deserializedResponseBody['expires_at'], 'string', 'expires_at');

        $this->accessToken = $deserializedResponseBody['access_token'];
        $this->expiresAt   = $deserializedResponseBody['expires_at'];
    }
}
