<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ComfinoExternal\Symfony\Component\Yaml;

use ComfinoExternal\Symfony\Component\Yaml\Exception\ParseException;

class Unescaper
{
    public const REGEX_ESCAPED_CHARACTER = '\\\\(x[0-9a-fA-F]{2}|u[0-9a-fA-F]{4}|U[0-9a-fA-F]{8}|.)';
    /**
     * @param string $value
     * @return string
     */
    public function unescapeSingleQuotedString(string $value): string
    {
        return str_replace('\'\'', '\'', $value);
    }
    /**
     * @param string $value
     * @return string
     */
    public function unescapeDoubleQuotedString(string $value): string
    {
        $callback = function ($match) {
            return $this->unescapeCharacter($match[0]);
        };
        
        return preg_replace_callback('/' . self::REGEX_ESCAPED_CHARACTER . '/u', $callback, $value);
    }
    /**
     * @param string $value
     * @return string
     */
    private function unescapeCharacter(string $value): string
    {
        switch ($value[1]) {
            case '0':
                return "\x00";
            case 'a':
                return "\x07";
            case 'b':
                return "\x08";
            case 't':
                return "\t";
            case "\t":
                return "\t";
            case 'n':
                return "\n";
            case 'v':
                return "\v";
            case 'f':
                return "\f";
            case 'r':
                return "\r";
            case 'e':
                return "\x1b";
            case ' ':
                return ' ';
            case '"':
                return '"';
            case '/':
                return '/';
            case '\\':
                return '\\';
            case 'N':
                
                return "";
            case '_':
                
                return " ";
            case 'L':
                
                return " ";
            case 'P':
                
                return " ";
            case 'x':
                return self::utf8chr(hexdec(substr($value, 2, 2)));
            case 'u':
                return self::utf8chr(hexdec(substr($value, 2, 4)));
            case 'U':
                return self::utf8chr(hexdec(substr($value, 2, 8)));
            default:
                throw new ParseException(sprintf('Found unknown escape character "%s".', $value));
        }
    }
    
    private static function utf8chr(int $c): string
    {
        if (0x80 > $c %= 0x200000) {
            return \chr($c);
        }
        if (0x800 > $c) {
            return \chr(0xc0 | $c >> 6) . \chr(0x80 | $c & 0x3f);
        }
        if (0x10000 > $c) {
            return \chr(0xe0 | $c >> 12) . \chr(0x80 | $c >> 6 & 0x3f) . \chr(0x80 | $c & 0x3f);
        }
        return \chr(0xf0 | $c >> 18) . \chr(0x80 | $c >> 12 & 0x3f) . \chr(0x80 | $c >> 6 & 0x3f) . \chr(0x80 | $c & 0x3f);
    }
}
