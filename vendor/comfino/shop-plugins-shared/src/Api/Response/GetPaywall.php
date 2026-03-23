<?php

declare(strict_types=1);

namespace Comfino\Api\Response;

class GetPaywall extends Base
{
    public $paywallBody;

    public $paywallHash;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'array');
        $this->checkResponseStructure($deserializedResponseBody, ['paywallBody', 'paywallHash']);
        $this->checkResponseType($deserializedResponseBody['paywallBody'], 'string');
        $this->checkResponseType($deserializedResponseBody['paywallHash'], 'string');

        $this->paywallBody = $deserializedResponseBody['paywallBody'];
        $this->paywallHash = $deserializedResponseBody['paywallHash'];
    }
}
