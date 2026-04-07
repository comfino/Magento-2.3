<?php

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace ComfinoExternal\Cache\Adapter\Common;

trait JsonBinaryArmoring
{
    private static $ESCAPE_JSON_CHARACTERS = ["\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\t", "\n", "\v", "\f", "\r", "\x0e", "\x0f", "\x10", "\x11", "\x12", "\x13", "\x14", "\x15", "\x16", "\x17", "\x18", "\x19", "\x1a", "\x1b", "\x1c", "\x1d", "\x1e", "\x1f"];
    private static $ENCODED_JSON_CHARACTERS = ['\u0000', '\u0001', '\u0002', '\u0003', '\u0004', '\u0005', '\u0006', '\u0007', '\u0008', '\u0009', '\u000A', '\u000B', '\u000C', '\u000D', '\u000E', '\u000F', '\u0010', '\u0011', '\u0012', '\u0013', '\u0014', '\u0015', '\u0016', '\u0017', '\u0018', '\u0019', '\u001A', '\u001B', '\u001C', '\u001D', '\u001E', '\u001F'];
    /**
     * @param string $value
     * @return string
     */
    protected static function jsonArmor($value)
    {
        return str_replace(static::$ESCAPE_JSON_CHARACTERS, static::$ENCODED_JSON_CHARACTERS, utf8_encode($value));
    }
    /**
     * @param string $value
     * @return string
     */
    protected static function jsonDeArmor($value)
    {
        return utf8_decode(str_replace(static::$ENCODED_JSON_CHARACTERS, static::$ESCAPE_JSON_CHARACTERS, $value));
    }
}
