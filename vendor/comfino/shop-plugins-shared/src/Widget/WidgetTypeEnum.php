<?php

declare(strict_types=1);

namespace Comfino\Widget;

use Comfino\Enum;

class WidgetTypeEnum extends Enum
{
    public const WIDGET_SIMPLE = 'simple';
    public const WIDGET_MIXED = 'mixed';
    public const WIDGET_WITH_CALCULATOR = 'with-modal';
    public const WIDGET_WITH_EXTENDED_CALCULATOR = 'extended-modal';

    /**
     * @param string $value
     * @param bool $strict
     * @return $this
     */
    public static function from($value, $strict = true): \Comfino\Enum
    {
        return new self($value, $strict);
    }
}
