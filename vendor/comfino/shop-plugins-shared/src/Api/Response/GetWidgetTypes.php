<?php

declare(strict_types=1);

namespace Comfino\Api\Response;

use Comfino\Widget\WidgetTypeEnum;

class GetWidgetTypes extends Base
{
    public $widgetTypes;

    public $widgetTypesWithNames;

    /**
     * @param mixed[]|string|bool|null|float|int $deserializedResponseBody
     */
    protected function processResponseBody($deserializedResponseBody): void
    {
        $this->checkResponseType($deserializedResponseBody, 'array');

        $this->widgetTypesWithNames = $deserializedResponseBody;
        $this->widgetTypes = array_map(
            static function (string $widgetType): WidgetTypeEnum {
                return WidgetTypeEnum::from($widgetType, false);
            },
            array_keys($deserializedResponseBody)
        );
    }
}
