<?php

namespace ComfinoExternal\Psr\Http\Message;

interface ResponseInterface extends MessageInterface
{
    /**
     * @return int
     */
    public function getStatusCode();
    /**
     * @param int $code
     * @param string $reasonPhrase
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withStatus($code, $reasonPhrase = '');
    /**
     * @return string
     */
    public function getReasonPhrase();
}
