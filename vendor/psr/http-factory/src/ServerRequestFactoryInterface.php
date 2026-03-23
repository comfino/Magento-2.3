<?php

namespace ComfinoExternal\Psr\Http\Message;

interface ServerRequestFactoryInterface
{
    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param array $serverParams
     * @return ServerRequestInterface
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface;
}
