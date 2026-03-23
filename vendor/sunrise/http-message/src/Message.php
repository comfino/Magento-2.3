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

use InvalidArgumentException;
use ComfinoExternal\Psr\Http\Message\MessageInterface;
use ComfinoExternal\Psr\Http\Message\StreamInterface;
use ComfinoExternal\Sunrise\Http\Header\HeaderInterface;
use ComfinoExternal\Sunrise\Stream\StreamFactory;

use function implode;
use function is_string;
use function preg_match;
use function sprintf;
use function strtolower;
use function ucwords;

class Message implements MessageInterface
{
    /**
     * @var string
     */
    protected $protocolVersion = '1.1';
    /**
     * @var array<string,
     */
    protected $headers = [];
    /**
     * @var StreamInterface|null
     */
    protected $body = null;
    /**
     * @param StreamInterface|null $body
     * @param string|null $protocolVersion
     */
    public function __construct(?array $headers = null, ?StreamInterface $body = null, ?string $protocolVersion = null)
    {
        if (isset($protocolVersion)) {
            $this->setProtocolVersion($protocolVersion);
        }
        if (isset($headers)) {
            foreach ($headers as $name => $value) {
                $this->addHeader($name, $value);
            }
        }
        if (isset($body)) {
            $this->body = $body;
        }
    }
    
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }
    
    public function withProtocolVersion($version): MessageInterface
    {
        $clone = clone $this;
        $clone->setProtocolVersion($version);
        return $clone;
    }
    
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    public function hasHeader($name): bool
    {
        $name = $this->normalizeHeaderName($name);
        return !empty($this->headers[$name]);
    }
    
    public function getHeader($name): array
    {
        $name = $this->normalizeHeaderName($name);
        if (empty($this->headers[$name])) {
            return [];
        }
        return $this->headers[$name];
    }
    
    public function getHeaderLine($name): string
    {
        $name = $this->normalizeHeaderName($name);
        if (empty($this->headers[$name])) {
            return '';
        }
        return implode(', ', $this->headers[$name]);
    }
    
    public function withHeader($name, $value): MessageInterface
    {
        $clone = clone $this;
        $clone->addHeader($name, $value);
        return $clone;
    }
    
    public function withAddedHeader($name, $value): MessageInterface
    {
        $clone = clone $this;
        $clone->addHeader($name, $value, \false);
        return $clone;
    }
    
    public function withoutHeader($name): MessageInterface
    {
        $name = $this->normalizeHeaderName($name);
        $clone = clone $this;
        unset($clone->headers[$name]);
        return $clone;
    }
    
    public function getBody(): StreamInterface
    {
        if (null === $this->body) {
            $this->body = (new StreamFactory())->createStream();
        }
        return $this->body;
    }
    
    public function withBody(StreamInterface $body): MessageInterface
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }
    /**
     * @param string $version
     * @return void
     */
    protected function setProtocolVersion($version): void
    {
        $this->validateProtocolVersion($version);
        $this->protocolVersion = $version;
    }
    /**
     * @param string $name
     * @param string|string[] $value
     * @param bool $replace
     * @return void
     */
    protected function addHeader($name, $value, bool $replace = \true): void
    {
        $this->validateHeaderName($name);
        $this->validateHeaderValue($value, $name);
        $name = $this->normalizeHeaderName($name);
        $value = (array) $value;
        if ($replace) {
            $this->headers[$name] = $value;
            return;
        }
        foreach ($value as $item) {
            $this->headers[$name][] = $item;
        }
    }
    /**
     * @param mixed $version
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateProtocolVersion($version): void
    {
        static $allowed = ['1.0' => \true, '1.1' => \true, '2.0' => \true, '2' => \true];
        if (!is_string($version)) {
            throw new InvalidArgumentException('HTTP version must be a string');
        }
        if (!isset($allowed[$version])) {
            throw new InvalidArgumentException(sprintf('The HTTP version "%s" is not valid, use only: 1.0, 1.1, 2{.0}', $version));
        }
    }
    /**
     * @param mixed $name
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateHeaderName($name): void
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Header name must be a string');
        }
        if (!preg_match(HeaderInterface::RFC7230_TOKEN, $name)) {
            throw new InvalidArgumentException(sprintf('The header name "%s" is not valid', $name));
        }
    }
    /**
     * @param mixed $value
     * @param string $name
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateHeaderValue($value, string $name): void
    {
        $items = (array) $value;
        if ([] === $items) {
            throw new InvalidArgumentException(sprintf('The header "%s" value must be a string or a non-empty array', $name));
        }
        foreach ($items as $item) {
            if (!is_string($item)) {
                throw new InvalidArgumentException(sprintf('The header "%s" value must be a string or an array with strings only', $name));
            }
            if (!preg_match(HeaderInterface::RFC7230_FIELD_VALUE, $item)) {
                throw new InvalidArgumentException(sprintf('The header "%s" value "%s" is not valid', $name, $item));
            }
        }
    }
    /**
     * @param string $name
     * @return string
     */
    protected function normalizeHeaderName($name): string
    {
        return ucwords(strtolower($name), '-');
    }
}
