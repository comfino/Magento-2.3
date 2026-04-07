<?php

declare(strict_types=1);

namespace Comfino\FinancialProduct;

use Comfino\Enum;

class ProductTypesListTypeEnum extends Enum
{
    public const LIST_TYPE_PAYWALL = 'paywall';
    public const LIST_TYPE_WIDGET = 'widget';

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
