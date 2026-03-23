<?php

namespace ComfinoExternal\Psr\Http\Client;

use ComfinoExternal\Psr\Http\Message\RequestInterface;

interface RequestExceptionInterface extends ClientExceptionInterface
{
    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface;
}
