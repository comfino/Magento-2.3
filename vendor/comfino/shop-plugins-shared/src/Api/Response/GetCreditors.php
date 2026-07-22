<?php

declare(strict_types=1);

namespace Comfino\Api\Response;

class GetCreditors extends Base
{
    public $creditors;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'array');

        $this->creditors = $deserializedResponseBody;
    }
}
