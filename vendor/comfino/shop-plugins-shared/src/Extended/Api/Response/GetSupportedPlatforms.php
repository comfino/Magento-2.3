<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Response;

use Comfino\Api\Response\Base;

class GetSupportedPlatforms extends Base
{
    public $platforms;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'array');
        $this->checkResponseStructure($deserializedResponseBody, ['platforms']);
        $this->checkResponseType($deserializedResponseBody['platforms'], 'array', 'platforms');

        $this->platforms = array_map(
            function ($platform): array {
                $this->checkResponseType($platform, 'array', 'platforms[]');
                $this->checkResponseStructure($platform, ['code', 'name']);

                return ['code' => $platform['code'], 'name' => $platform['name']];
            },
            $deserializedResponseBody['platforms']
        );
    }
}
