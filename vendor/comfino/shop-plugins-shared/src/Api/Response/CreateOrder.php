<?php

declare(strict_types=1);

namespace Comfino\Api\Response;

class CreateOrder extends Base
{
    public $status;

    public $externalId;

    public $applicationUrl;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'array');
        $this->checkResponseStructure($deserializedResponseBody, ['status', 'externalId', 'applicationUrl']);
        $this->checkResponseType($deserializedResponseBody['status'], 'string', 'status');
        $this->checkResponseType($deserializedResponseBody['externalId'], 'string', 'externalId');
        $this->checkResponseType($deserializedResponseBody['applicationUrl'], 'string', 'applicationUrl');

        $this->status = $deserializedResponseBody['status'];
        $this->externalId = $deserializedResponseBody['externalId'];
        $this->applicationUrl = $deserializedResponseBody['applicationUrl'];
    }
}
