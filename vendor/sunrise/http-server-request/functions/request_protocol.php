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

use function sprintf;
use function sscanf;
/**
     * @param array $server
     * @return string
     */
function request_protocol(array $server): string
{
    if (!isset($server['SERVER_PROTOCOL'])) {
        return '1.1';
    }
    
    sscanf($server['SERVER_PROTOCOL'], 'HTTP/%d.%d', $major, $minor);
    
    if (isset($minor)) {
        return sprintf('%d.%d', $major, $minor);
    }
    
    if (isset($major)) {
        return sprintf('%d', $major);
    }
    return '1.1';
}
