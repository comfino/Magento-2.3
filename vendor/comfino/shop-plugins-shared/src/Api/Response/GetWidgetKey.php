<?php

declare(strict_types=1);

namespace Comfino\Api\Response;

class GetWidgetKey extends Base
{
    public $widgetKey;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'string');

        $this->widgetKey = $deserializedResponseBody;
    }
}
