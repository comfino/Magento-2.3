<?php

namespace ComfinoExternal\Psr\Http\Message;

interface StreamFactoryInterface
{
    /**
     * @param string $content
     * @return StreamInterface
     */
    public function createStream(string $content = ''): StreamInterface;
    /**
     * @param string $filename
     * @param string $mode
     * @return StreamInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface;
    /**
     * @param resource $resource
     * @return StreamInterface
     */
    public function createStreamFromResource($resource): StreamInterface;
}
