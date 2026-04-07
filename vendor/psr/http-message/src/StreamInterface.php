<?php

namespace ComfinoExternal\Psr\Http\Message;

interface StreamInterface
{
    /**
     * @return string
     */
    public function __toString();
    /**
     * @return void
     */
    public function close();
    /**
     * @return resource|null
     */
    public function detach();
    /**
     * @return int|null
     */
    public function getSize();
    /**
     * @return int
     * @throws \RuntimeException
     */
    public function tell();
    /**
     * @return bool
     */
    public function eof();
    /**
     * @return bool
     */
    public function isSeekable();
    /**
     * @param int $offset
     * @param int $whence
     * @throws \RuntimeException
     */
    public function seek($offset, $whence = \SEEK_SET);
    /**
     * @throws \RuntimeException
     */
    public function rewind();
    /**
     * @return bool
     */
    public function isWritable();
    /**
     * @param string $string
     * @return int
     * @throws \RuntimeException
     */
    public function write($string);
    /**
     * @return bool
     */
    public function isReadable();
    /**
     * @param int $length
     * @return string
     * @throws \RuntimeException
     */
    public function read($length);
    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getContents();
    /**
     * @param string $key
     * @return array|mixed|null
     */
    public function getMetadata($key = null);
}
