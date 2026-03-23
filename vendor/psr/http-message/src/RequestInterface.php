<?php

namespace ComfinoExternal\Psr\Http\Message;

interface RequestInterface extends MessageInterface
{
    /**
     * @return string
     */
    public function getRequestTarget();
    /**
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget($requestTarget);
    /**
     * @return string
     */
    public function getMethod();
    /**
     * @param string $method
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withMethod($method);
    /**
     * @return UriInterface
     */
    public function getUri();
    /**
     * @param UriInterface $uri
     * @param bool $preserveHost
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = \false);
}
