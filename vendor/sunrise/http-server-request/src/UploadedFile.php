<?php

declare (strict_types=1);
/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2018, Anatoly Fenric
 * @license https://github.com/sunrise-php/http-server-request/blob/master/LICENSE
 * @link https://github.com/sunrise-php/http-server-request
 */
namespace ComfinoExternal\Sunrise\Http\ServerRequest;

use ComfinoExternal\Psr\Http\Message\StreamInterface;
use ComfinoExternal\Psr\Http\Message\UploadedFileInterface;
use ComfinoExternal\Sunrise\Stream\StreamFactory;
use InvalidArgumentException;
use RuntimeException;

use function dirname;
use function is_dir;
use function is_writeable;
use function sprintf;

use const ComfinoExternal\Sunrise\Http\ServerRequest\UPLOAD_ERRORS;
use const UPLOAD_ERR_OK;

class UploadedFile implements UploadedFileInterface
{
    /**
     * @var array<int,
     */
    public const UPLOAD_ERRORS = UPLOAD_ERRORS;
    /**
     * @var StreamInterface|null
     */
    protected $stream = null;
    /**
     * @var int|null
     */
    protected $size;
    /**
     * @var int
     */
    protected $error;
    /**
     * @var string
     */
    protected $errorMessage;
    /**
     * @var string|null
     */
    protected $clientFilename;
    /**
     * @var string|null
     */
    protected $clientMediaType;
    /**
     * @param StreamInterface|string $file
     * @param int|null $size
     * @param int $error
     * @param string|null $clientFilename
     * @param string|null $clientMediaType
     */
    public function __construct($file, ?int $size = null, int $error = UPLOAD_ERR_OK, ?string $clientFilename = null, ?string $clientMediaType = null)
    {
        if (UPLOAD_ERR_OK === $error) {
            $this->stream = $this->createStream($file);
        }
        $this->size = $size;
        $this->error = $error;
        
        $errorMessage = static::UPLOAD_ERRORS[$error] ?? 'Unknown error';
        $this->errorMessage = $errorMessage;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }
    /**
     * @throws RuntimeException
     */
    public function getStream(): StreamInterface
    {
        if (UPLOAD_ERR_OK != $this->error) {
            throw new RuntimeException(sprintf('The uploaded file has no a stream due to the error #%d (%s)', $this->error, $this->errorMessage));
        }
        if (!$this->stream instanceof StreamInterface) {
            throw new RuntimeException('The uploaded file already moved');
        }
        return $this->stream;
    }
    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function moveTo($targetPath): void
    {
        if (UPLOAD_ERR_OK != $this->error) {
            throw new RuntimeException(sprintf('The uploaded file cannot be moved due to the error #%d (%s)', $this->error, $this->errorMessage));
        }
        if (!$this->stream instanceof StreamInterface) {
            throw new RuntimeException('The uploaded file already moved');
        }
        $folder = dirname($targetPath);
        if (!is_dir($folder) || !is_writeable($folder)) {
            throw new InvalidArgumentException(sprintf('The uploaded file cannot be moved because the directory "%s" is not available', $folder));
        }
        $target = (new StreamFactory())->createStreamFromFile($targetPath, 'wb');
        $this->stream->rewind();
        while (!$this->stream->eof()) {
            $piece = $this->stream->read(4096);
            $target->write($piece);
        }
        $this->stream->close();
        $this->stream = null;
        $target->close();
    }
    
    public function getSize(): ?int
    {
        return $this->size;
    }
    
    public function getError(): int
    {
        return $this->error;
    }
    
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }
    
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
    /**
     * @param mixed $file
     * @return StreamInterface
     * @throws InvalidArgumentException
     */
    protected function createStream($file): StreamInterface
    {
        if ($file instanceof StreamInterface) {
            return $file;
        }
        if (is_string($file)) {
            return (new StreamFactory())->createStreamFromFile($file, 'rb');
        }
        throw new InvalidArgumentException('Invalid uploaded file');
    }
}
