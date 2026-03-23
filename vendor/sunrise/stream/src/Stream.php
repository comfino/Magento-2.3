<?php

declare (strict_types=1);
/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2018, Anatoly Fenric
 * @license https://github.com/sunrise-php/stream/blob/master/LICENSE
 * @link https://github.com/sunrise-php/stream
 */
namespace ComfinoExternal\Sunrise\Stream;

use InvalidArgumentException;
use ComfinoExternal\Psr\Http\Message\StreamInterface;
use ComfinoExternal\Sunrise\Stream\Exception\UnreadableStreamException;
use ComfinoExternal\Sunrise\Stream\Exception\UnseekableStreamException;
use ComfinoExternal\Sunrise\Stream\Exception\UntellableStreamException;
use ComfinoExternal\Sunrise\Stream\Exception\UnwritableStreamException;
use Throwable;

use function fclose;
use function feof;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function is_resource;
use function stream_get_contents;
use function stream_get_meta_data;
use function strpbrk;

use const SEEK_SET;

class Stream implements StreamInterface
{
    /**
     * @var resource|null
     */
    protected $resource;
    /**
     * @param resource $resource
     * @throws InvalidArgumentException
     */
    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Invalid stream resource');
        }
        $this->resource = $resource;
    }
    /**
     * @return resource|null
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }
    /**
     * @return void
     */
    public function close(): void
    {
        $resource = $this->detach();
        if (!is_resource($resource)) {
            return;
        }
        fclose($resource);
    }
    /**
     * @return bool
     */
    public function eof(): bool
    {
        if (!is_resource($this->resource)) {
            return \true;
        }
        return feof($this->resource);
    }
    /**
     * @return int
     * @throws UntellableStreamException
     */
    public function tell(): int
    {
        if (!is_resource($this->resource)) {
            throw new UntellableStreamException('Stream is not resourceable');
        }
        $result = ftell($this->resource);
        if ($result === \false) {
            throw new UntellableStreamException('Unable to get the stream pointer position');
        }
        return $result;
    }
    /**
     * @return bool
     */
    public function isSeekable(): bool
    {
        if (!is_resource($this->resource)) {
            return \false;
        }
        
        $metadata = stream_get_meta_data($this->resource);
        return $metadata['seekable'];
    }
    /**
     * @return void
     * @throws UnseekableStreamException
     */
    public function rewind(): void
    {
        if (!is_resource($this->resource)) {
            throw new UnseekableStreamException('Stream is not resourceable');
        }
        if (!$this->isSeekable()) {
            throw new UnseekableStreamException('Stream is not seekable');
        }
        $result = fseek($this->resource, 0, SEEK_SET);
        if ($result !== 0) {
            throw new UnseekableStreamException('Unable to move the stream pointer to beginning');
        }
    }
    /**
     * @param int $offset
     * @param int $whence
     * @return void
     * @throws UnseekableStreamException
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!is_resource($this->resource)) {
            throw new UnseekableStreamException('Stream is not resourceable');
        }
        if (!$this->isSeekable()) {
            throw new UnseekableStreamException('Stream is not seekable');
        }
        $result = fseek($this->resource, $offset, $whence);
        if ($result !== 0) {
            throw new UnseekableStreamException('Unable to move the stream pointer to the given position');
        }
    }
    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        if (!is_resource($this->resource)) {
            return \false;
        }
        
        $metadata = stream_get_meta_data($this->resource);
        return strpbrk($metadata['mode'], '+acwx') !== \false;
    }
    /**
     * @param string $string
     * @return int
     * @throws UnwritableStreamException
     */
    public function write($string): int
    {
        if (!is_resource($this->resource)) {
            throw new UnwritableStreamException('Stream is not resourceable');
        }
        if (!$this->isWritable()) {
            throw new UnwritableStreamException('Stream is not writable');
        }
        $result = fwrite($this->resource, $string);
        if ($result === \false) {
            throw new UnwritableStreamException('Unable to write to the stream');
        }
        return $result;
    }
    /**
     * @return bool
     */
    public function isReadable(): bool
    {
        if (!is_resource($this->resource)) {
            return \false;
        }
        
        $metadata = stream_get_meta_data($this->resource);
        return strpbrk($metadata['mode'], '+r') !== \false;
    }
    /**
     * @param int $length
     * @return string
     * @throws UnreadableStreamException
     */
    public function read($length): string
    {
        if (!is_resource($this->resource)) {
            throw new UnreadableStreamException('Stream is not resourceable');
        }
        if (!$this->isReadable()) {
            throw new UnreadableStreamException('Stream is not readable');
        }
        $result = fread($this->resource, $length);
        if ($result === \false) {
            throw new UnreadableStreamException('Unable to read from the stream');
        }
        return $result;
    }
    /**
     * @return string
     * @throws UnreadableStreamException
     */
    public function getContents(): string
    {
        if (!is_resource($this->resource)) {
            throw new UnreadableStreamException('Stream is not resourceable');
        }
        if (!$this->isReadable()) {
            throw new UnreadableStreamException('Stream is not readable');
        }
        $result = stream_get_contents($this->resource);
        if ($result === \false) {
            throw new UnreadableStreamException('Unable to read remainder of the stream');
        }
        return $result;
    }
    /**
     * @param string $key
     * @return mixed
     */
    public function getMetadata($key = null)
    {
        if (!is_resource($this->resource)) {
            return null;
        }
        $metadata = stream_get_meta_data($this->resource);
        if ($key === null) {
            return $metadata;
        }
        return $metadata[$key] ?? null;
    }
    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        if (!is_resource($this->resource)) {
            return null;
        }
        
        $stats = fstat($this->resource);
        if ($stats === \false) {
            return null;
        }
        return $stats['size'];
    }
    /**
     * @return string
     */
    public function __toString()
    {
        try {
            if ($this->isReadable()) {
                if ($this->isSeekable()) {
                    $this->rewind();
                }
                return $this->getContents();
            }
        } catch (Throwable $e) {
        }
        return '';
    }
}
