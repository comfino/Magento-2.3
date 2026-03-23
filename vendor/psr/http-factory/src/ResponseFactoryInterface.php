<?php

namespace ComfinoExternal\Psr\Http\Message;

interface ResponseFactoryInterface
{
    /**
     * @param int $code
     * @param string $reasonPhrase
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface;
}
