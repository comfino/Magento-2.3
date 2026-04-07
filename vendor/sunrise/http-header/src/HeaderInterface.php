<?php

declare (strict_types=1);
/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2018, Anatoly Fenric
 * @license https://github.com/sunrise-php/http-header/blob/master/LICENSE
 * @link https://github.com/sunrise-php/http-header
 */
namespace ComfinoExternal\Sunrise\Http\Header;

interface HeaderInterface
{
    /**
     * @var string
     */
    public const RFC7230_TOKEN = '/^[\x21\x23-\x27\x2A\x2B\x2D\x2E\x30-\x39\x41-\x5A\x5E-\x7A\x7C\x7E]+$/';
    /**
     * @var string
     */
    public const RFC7230_FIELD_VALUE = '/^[\x09\x20-\x7E\x80-\xFF]*$/';
    /**
     * @var string
     */
    public const RFC7230_QUOTED_STRING = '/^[\x09\x20\x21\x23-\x5B\x5D-\x7E\x80-\xFF]*$/';
    /**
     * @return string
     */
    public function getFieldName(): string;
    /**
     * @return string
     */
    public function getFieldValue(): string;
    /**
     * @return string
     */
    public function __toString();
}
