<?php

declare (strict_types=1);
/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2018, Anatoly Fenric
 * @license https://github.com/sunrise-php/http-message/blob/master/LICENSE
 * @link https://github.com/sunrise-php/http-message
 */
namespace ComfinoExternal\Sunrise\Http\Message;

use ComfinoExternal\Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;
use ComfinoExternal\Psr\Http\Message\RequestInterface;
use ComfinoExternal\Psr\Http\Message\StreamInterface;
use ComfinoExternal\Psr\Http\Message\UriInterface;
use ComfinoExternal\Sunrise\Http\Header\HeaderInterface;
use ComfinoExternal\Sunrise\Uri\UriFactory;

use function is_string;
use function preg_match;
use function sprintf;
use function strncmp;
use function strtoupper;

class Request extends Message implements RequestInterface, RequestMethodInterface
{
    /**
     * @var string
     */
    protected $method = self::METHOD_GET;
    /**
     * @var string|null
     */
    protected $requestTarget = null;
    /**
     * @var UriInterface|null
     */
    protected $uri = null;
    /**
     * @param string|null $method
     * @param string|UriInterface|null $uri
     * @param StreamInterface|null $body
     * @param string|null $requestTarget
     * @param string|null $protocolVersion
     */
    public function __construct(?string $method = null, $uri = null, ?array $headers = null, ?StreamInterface $body = null, ?string $requestTarget = null, ?string $protocolVersion = null)
    {
        parent::__construct($headers, $body, $protocolVersion);
        if (isset($method)) {
            $this->setMethod($method);
        }
        if (isset($requestTarget)) {
            $this->setRequestTarget($requestTarget);
        }
        if (isset($uri)) {
            $this->setUri($uri);
        }
    }
    
    public function getMethod(): string
    {
        return $this->method;
    }
    
    public function withMethod($method): RequestInterface
    {
        $clone = clone $this;
        $clone->setMethod($method);
        return $clone;
    }
    
    public function getRequestTarget(): string
    {
        if (isset($this->requestTarget)) {
            return $this->requestTarget;
        }
        $uri = $this->getUri();
        $path = $uri->getPath();

        if (0 !== strncmp($path, '/', 1)) {
            return '/';
        }
        $requestTarget = $path;
        $query = $uri->getQuery();
        if ('' !== $query) {
            $requestTarget .= '?' . $query;
        }
        return $requestTarget;
    }
    
    public function withRequestTarget($requestTarget): RequestInterface
    {
        $clone = clone $this;
        $clone->setRequestTarget($requestTarget);
        return $clone;
    }
    
    public function getUri(): UriInterface
    {
        if (null === $this->uri) {
            $this->uri = (new UriFactory())->createUri();
        }
        return $this->uri;
    }
    
    public function withUri(UriInterface $uri, $preserveHost = \false): RequestInterface
    {
        $clone = clone $this;
        $clone->setUri($uri, $preserveHost);
        return $clone;
    }
    /**
     * @param string $method
     * @return void
     */
    protected function setMethod($method): void
    {
        $this->validateMethod($method);
        $this->method = strtoupper($method);
    }
    /**
     * @param mixed $requestTarget
     * @return void
     */
    protected function setRequestTarget($requestTarget): void
    {
        $this->validateRequestTarget($requestTarget);
        /**
     * @var string $requestTarget
     */
        $this->requestTarget = $requestTarget;
    }
    /**
     * @param string|UriInterface $uri
     * @param bool $preserveHost
     * @return void
     */
    protected function setUri($uri, $preserveHost = \false): void
    {
        if (!$uri instanceof UriInterface) {
            $uri = (new UriFactory())->createUri($uri);
        }
        $this->uri = $uri;
        if ('' === $uri->getHost() || $preserveHost && $this->hasHeader('Host')) {
            return;
        }
        $newHost = $uri->getHost();
        $port = $uri->getPort();
        if (null !== $port) {
            $newHost .= ':' . $port;
        }
        $this->addHeader('Host', $newHost);
    }
    /**
     * @param mixed $method
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateMethod($method): void
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException('HTTP method must be a string');
        }
        if (!preg_match(HeaderInterface::RFC7230_TOKEN, $method)) {
            throw new InvalidArgumentException(sprintf('HTTP method "%s" is not valid', $method));
        }
    }
    /**
     * @param mixed $requestTarget
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateRequestTarget($requestTarget): void
    {
        if (!is_string($requestTarget)) {
            throw new InvalidArgumentException('HTTP request-target must be a string');
        }
        if (!preg_match('/^[\x21-\x7E\x80-\xFF]+$/', $requestTarget)) {
            throw new InvalidArgumentException(sprintf('HTTP request-target "%s" is not valid', $requestTarget));
        }
    }
}
