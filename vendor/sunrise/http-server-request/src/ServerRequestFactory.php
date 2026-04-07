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

use ComfinoExternal\Psr\Http\Message\ServerRequestFactoryInterface;
use ComfinoExternal\Psr\Http\Message\ServerRequestInterface;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * @param array|null $serverParams
     * @param array|null $queryParams
     * @param array|null $cookieParams
     * @param array|null $uploadedFiles
     * @param array|null $parsedBody
     * @return ServerRequestInterface
     */
    public static function fromGlobals(?array $serverParams = null, ?array $queryParams = null, ?array $cookieParams = null, ?array $uploadedFiles = null, ?array $parsedBody = null): ServerRequestInterface
    {
        $serverParams = $serverParams ?? $_SERVER;
        $queryParams = $queryParams ?? $_GET;
        $cookieParams = $cookieParams ?? $_COOKIE;
        $uploadedFiles = $uploadedFiles ?? $_FILES;
        $parsedBody = $parsedBody ?? $_POST;
        return new ServerRequest(
            request_method($serverParams),
            request_uri($serverParams),
            request_headers($serverParams),
            request_body(),
            null,
            
            request_protocol($serverParams),
            $serverParams,
            $queryParams,
            $cookieParams,
            request_files($uploadedFiles),
            $parsedBody
        );
    }
    
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest(
            $method,
            $uri,
            request_headers($serverParams),
            null,
            
            null,
            
            request_protocol($serverParams),
            $serverParams
        );
    }
}
