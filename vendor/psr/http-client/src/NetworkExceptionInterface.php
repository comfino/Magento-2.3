<?php

namespace ComfinoExternal\Psr\Http\Client;

use ComfinoExternal\Psr\Http\Message\RequestInterface;

interface NetworkExceptionInterface extends ClientExceptionInterface
{
    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface;
}
