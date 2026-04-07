<?php

namespace ComfinoExternal\Psr\Http\Message;

interface RequestFactoryInterface
{
    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @return RequestInterface
     */
    public function createRequest(string $method, $uri): RequestInterface;
}
