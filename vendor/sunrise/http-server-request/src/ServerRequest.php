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

use InvalidArgumentException;
use ComfinoExternal\Psr\Http\Message\ServerRequestInterface;
use ComfinoExternal\Psr\Http\Message\StreamInterface;
use ComfinoExternal\Psr\Http\Message\UploadedFileInterface;
use ComfinoExternal\Psr\Http\Message\UriInterface;
use ComfinoExternal\Sunrise\Http\Message\Request;

use function array_key_exists;
use function array_walk_recursive;
use function is_array;
use function is_object;

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array
     */
    protected $serverParams;
    /**
     * @var array
     */
    protected $queryParams;
    /**
     * @var array
     */
    protected $cookieParams;
    /**
     * @var array
     */
    protected $uploadedFiles;
    /**
     * @var array|object|null
     */
    protected $parsedBody;
    /**
     * @var array
     */
    protected $attributes;
    /**
     * @param string|null $method
     * @param string|UriInterface|null $uri
     * @param StreamInterface|null $body
     * @param string|null $requestTarget
     * @param string|null $protocolVersion
     * @param array $serverParams
     * @param array $queryParams
     * @param array $cookieParams
     * @param array $uploadedFiles
     * @param array|object|null $parsedBody
     * @param array $attributes
     */
    public function __construct(?string $method = null, $uri = null, ?array $headers = null, ?StreamInterface $body = null, ?string $requestTarget = null, ?string $protocolVersion = null, array $serverParams = [], array $queryParams = [], array $cookieParams = [], array $uploadedFiles = [], $parsedBody = null, array $attributes = [])
    {
        parent::__construct($method, $uri, $headers, $body, $requestTarget, $protocolVersion);
        $this->serverParams = $serverParams;
        $this->queryParams = $queryParams;
        $this->cookieParams = $cookieParams;
        $this->setUploadedFiles($uploadedFiles);
        $this->setParsedBody($parsedBody);
        $this->attributes = $attributes;
    }
    
    public function getServerParams(): array
    {
        return $this->serverParams;
    }
    
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }
    /**
     * @psalm-suppress ParamNameMismatch
     */
    public function withQueryParams(array $queryParams): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->queryParams = $queryParams;
        return $clone;
    }
    
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }
    /**
     * @psalm-suppress ParamNameMismatch
     */
    public function withCookieParams(array $cookieParams): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->cookieParams = $cookieParams;
        return $clone;
    }
    
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }
    
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->setUploadedFiles($uploadedFiles);
        return $clone;
    }
    
    public function getParsedBody()
    {
        return $this->parsedBody;
    }
    /**
     * @psalm-suppress ParamNameMismatch
     */
    public function withParsedBody($parsedBody): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->setParsedBody($parsedBody);
        return $clone;
    }
    
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    public function getAttribute($name, $default = null)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        return $default;
    }
    
    public function withAttribute($name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }
    
    public function withoutAttribute($name): ServerRequestInterface
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }
    /**
     * @param array $files
     * @return void
     */
    protected function setUploadedFiles(array $files): void
    {
        $this->validateUploadedFiles($files);
        $this->uploadedFiles = $files;
    }
    /**
     * @param array|object|null $data
     * @return void
     */
    protected function setParsedBody($data): void
    {
        $this->validateParsedBody($data);
        $this->parsedBody = $data;
    }
    /**
     * @param array $files
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateUploadedFiles(array $files): void
    {
        if ([] === $files) {
            return;
        }
        /**
     * @param mixed $file
     * @return void
     * @psalm-suppress MissingClosureParamType
     */
        array_walk_recursive($files, static function ($file): void {
            if (!$file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException('Invalid uploaded files');
            }
        });
    }
    /**
     * @param mixed $data
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateParsedBody($data): void
    {
        if (null === $data) {
            return;
        }
        if (!is_array($data) && !is_object($data)) {
            throw new InvalidArgumentException('Invalid parsed body');
        }
    }
}
