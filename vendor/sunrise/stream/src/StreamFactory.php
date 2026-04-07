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

use ComfinoExternal\Psr\Http\Message\StreamFactoryInterface;
use ComfinoExternal\Psr\Http\Message\StreamInterface;
use ComfinoExternal\Sunrise\Stream\Exception\UnopenableStreamException;

use function fopen;
use function sprintf;
use function tmpfile;

class StreamFactory implements StreamFactoryInterface
{
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }
    /**
     * @throws UnopenableStreamException
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = @fopen($filename, $mode);
        if ($resource === \false) {
            throw new UnopenableStreamException(sprintf('Unable to open file "%s" in mode "%s"', $filename, $mode));
        }
        return $this->createStreamFromResource($resource);
    }
    /**
     * @param string|null $content
     * @return StreamInterface
     * @throws UnopenableStreamException
     */
    public function createStreamFromTemporaryFile(?string $content = null): StreamInterface
    {
        $resource = tmpfile();
        if ($resource === \false) {
            throw new UnopenableStreamException('Unable to create temporary file');
        }
        $stream = $this->createStreamFromResource($resource);
        if ($content === null) {
            return $stream;
        }
        $stream->write($content);
        $stream->rewind();
        return $stream;
    }
    
    public function createStream(string $content = ''): StreamInterface
    {
        $stream = $this->createStreamFromFile('php://temp', 'r+b');
        if ($content === '') {
            return $stream;
        }
        $stream->write($content);
        $stream->rewind();
        return $stream;
    }
}
