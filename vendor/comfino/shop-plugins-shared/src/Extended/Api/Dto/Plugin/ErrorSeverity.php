<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Dto\Plugin;

use Comfino\Enum;

class ErrorSeverity extends Enum
{
    public const Critical = 'critical';

    public const Error = 'error';

    public const Warning = 'warning';

    public const Notice = 'notice';

    public const Info = 'info';

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
