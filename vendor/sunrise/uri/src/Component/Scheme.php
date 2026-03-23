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

use function is_string;
use function preg_match;
use function strtolower;

class Scheme implements ComponentInterface
{
    /**
     * @var string
     */
    private const VALIDATE_REGEX = '/^(?:[A-Za-z][0-9A-Za-z\+\-\.]*)?$/';
    /**
     * @var string
     */
    protected $value = '';
    /**
     * @param mixed $value
     * @throws InvalidUriComponentException
     */
    public function __construct($value)
    {
        if ($value === '') {
            return;
        }
        if (!is_string($value)) {
            throw new InvalidUriComponentException('URI component "scheme" must be a string');
        }
        if (!preg_match(self::VALIDATE_REGEX, $value)) {
            throw new InvalidUriComponentException('Invalid URI component "scheme"');
        }
        $this->value = $value;
    }
    /**
     * @return string
     */
    public function present(): string
    {
        return strtolower($this->value);
    }
}
