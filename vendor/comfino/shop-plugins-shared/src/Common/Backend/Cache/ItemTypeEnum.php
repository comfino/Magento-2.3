<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Cache;

use Comfino\Enum;

class ItemTypeEnum extends Enum
{
    public const PAYWALL_TEMPLATE = 'paywall_template';
    public const PAYWALL_STYLE = 'paywall_style';
    public const PAYWALL_SCRIPT = 'paywall_script';
    public const PAYWALL_FRONTEND_STYLE = 'paywall_frontend_style';
    public const PAYWALL_FRONTEND_SCRIPT = 'paywall_frontend_script';
    public const ADMIN_PRODUCT_TYPES = 'admin_product_types';
    public const ADMIN_WIDGET_TYPES = 'admin_widget_types';

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
