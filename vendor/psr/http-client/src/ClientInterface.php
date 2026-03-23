<?php

namespace ComfinoExternal\Psr\Http\Client;

use ComfinoExternal\Psr\Http\Message\RequestInterface;
use ComfinoExternal\Psr\Http\Message\ResponseInterface;
interface ClientInterface
{
    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
