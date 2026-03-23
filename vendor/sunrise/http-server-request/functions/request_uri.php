<?php

declare (strict_types=1);
/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2018, Anatoly Fenric
 * @license https://github.com/sunrise-php/http-server-request/blob/master/LICENSE
 * @link https://github.com/sunrise-php/http-server-request
 */
namespace ComfinoExternal\Sunrise\Http\ServerRequest;

use ComfinoExternal\Psr\Http\Message\UriInterface;
use ComfinoExternal\Sunrise\Uri\UriFactory;

use function array_key_exists;
/**
     * @param array $server
     * @return UriInterface
     */
function request_uri(array $server): UriInterface
{
    if (array_key_exists('HTTPS', $server)) {
        if (!('off' === $server['HTTPS'])) {
            $scheme = 'https://';
        }
    }
    if (array_key_exists('HTTP_HOST', $server)) {
        $host = $server['HTTP_HOST'];
    } elseif (array_key_exists('SERVER_NAME', $server)) {
        $host = $server['SERVER_NAME'];
        if (array_key_exists('SERVER_PORT', $server)) {
            $host .= ':' . $server['SERVER_PORT'];
        }
    }
    if (array_key_exists('REQUEST_URI', $server)) {
        $target = $server['REQUEST_URI'];
    } elseif (array_key_exists('PHP_SELF', $server)) {
        $target = $server['PHP_SELF'];
        if (array_key_exists('QUERY_STRING', $server)) {
            $target .= '?' . $server['QUERY_STRING'];
        }
    }
    return (new UriFactory())->createUri(($scheme ?? 'http://') . ($host ?? 'localhost') . ($target ?? '/'));
}
