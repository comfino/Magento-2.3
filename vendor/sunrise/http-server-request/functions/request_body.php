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

use ComfinoExternal\Psr\Http\Message\StreamInterface;
use ComfinoExternal\Sunrise\Stream\StreamFactory;

use function fopen;
use function rewind;
use function stream_copy_to_stream;
/**
     * @return StreamInterface
     */
function request_body(): StreamInterface
{
    $input = fopen('php://input', 'rb');
    $resource = fopen('php://temp', 'r+b');
    stream_copy_to_stream($input, $resource);
    rewind($resource);
    return (new StreamFactory())->createStreamFromResource($resource);
}
