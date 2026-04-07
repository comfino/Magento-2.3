<?php

namespace ComfinoExternal\Psr\Http\Message;

interface UriFactoryInterface
{
    /**
     * @param string $uri
     * @return UriInterface
     * @throws \InvalidArgumentException
     */
    public function createUri(string $uri = ''): UriInterface;
}
