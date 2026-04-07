<?php

declare (strict_types=1);
/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2018, Anatoly Fenric
 * @license https://github.com/sunrise-php/uri/blob/master/LICENSE
 * @link https://github.com/sunrise-php/uri
 */
namespace ComfinoExternal\Sunrise\Uri\Component;

use ComfinoExternal\Sunrise\Uri\Exception\InvalidUriComponentException;

use function is_int;

class Port implements ComponentInterface
{
    /**
     * @var int|null
     */
    protected $value;
    /**
     * @param mixed $value
     * @throws InvalidUriComponentException
     */
    public function __construct($value)
    {
        $min = 1;
        $max = 2 ** 16;
        if ($value === null) {
            return;
        }
        if (!is_int($value)) {
            throw new InvalidUriComponentException('URI component "port" must be an integer');
        }
        if (!($value >= $min && $value <= $max)) {
            throw new InvalidUriComponentException('Invalid URI component "port"');
        }
        $this->value = $value;
    }
    /**
     * @return int|null
     */
    public function present(): ?int
    {
        return $this->value;
    }
}
