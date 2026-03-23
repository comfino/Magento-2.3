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

class Yaml
{
    public const DUMP_OBJECT = 1;
    public const PARSE_EXCEPTION_ON_INVALID_TYPE = 2;
    public const PARSE_OBJECT = 4;
    public const PARSE_OBJECT_FOR_MAP = 8;
    public const DUMP_EXCEPTION_ON_INVALID_TYPE = 16;
    public const PARSE_DATETIME = 32;
    public const DUMP_OBJECT_AS_MAP = 64;
    public const DUMP_MULTI_LINE_LITERAL_BLOCK = 128;
    public const PARSE_CONSTANT = 256;
    public const PARSE_CUSTOM_TAGS = 512;
    public const DUMP_EMPTY_ARRAY_AS_SEQUENCE = 1024;
    public const DUMP_NULL_AS_TILDE = 2048;
    /**
     * @param string $filename
     * @param int $flags
     * @return mixed
     * @throws ParseException
     */
    public static function parseFile(string $filename, int $flags = 0)
    {
        $yaml = new Parser();
        return $yaml->parseFile($filename, $flags);
    }
    /**
     * @param string $input
     * @param int $flags
     * @return mixed
     * @throws ParseException
     */
    public static function parse(string $input, int $flags = 0)
    {
        $yaml = new Parser();
        return $yaml->parse($input, $flags);
    }
    /**
     * @param mixed $input
     * @param int $inline
     * @param int $indent
     * @param int $flags
     * @return string
     */
    public static function dump($input, int $inline = 2, int $indent = 4, int $flags = 0): string
    {
        $yaml = new Dumper($indent);
        return $yaml->dump($input, $inline, 0, $flags);
    }
}
