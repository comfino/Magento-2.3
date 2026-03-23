<?php

namespace ComfinoExternal\Psr\Http\Message;

interface MessageInterface
{
    /**
     * @return string
     */
    public function getProtocolVersion();
    /**
     * @param string $version
     * @return static
     */
    public function withProtocolVersion($version);
    /**
     * @return string[][]
     */
    public function getHeaders();
    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader($name);
    /**
     * @param string $name
     * @return string[]
     */
    public function getHeader($name);
    /**
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name);
    /**
     * @param string $name
     * @param string|string[] $value
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withHeader($name, $value);
    /**
     * @param string $name
     * @param string|string[] $value
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withAddedHeader($name, $value);
    /**
     * @param string $name
     * @return static
     */
    public function withoutHeader($name);
    /**
     * @return StreamInterface
     */
    public function getBody();
    /**
     * @param StreamInterface $body
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withBody(StreamInterface $body);
}
