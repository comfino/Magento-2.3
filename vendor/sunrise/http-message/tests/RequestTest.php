<?php

declare(strict_types=1);

namespace Sunrise\Http\Message\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Sunrise\Http\Message\Message;
use Sunrise\Http\Message\Request;
use Sunrise\Stream\StreamFactory;
use Sunrise\Uri\UriFactory;

class RequestTest extends TestCase
{
    /**
     * @return void
     */
    public function testContracts() : void
    {
        $mess = new Request();

        $this->assertInstanceOf(Message::class, $mess);
        $this->assertInstanceOf(RequestInterface::class, $mess);
    }

    /**
     * @return void
     */
    public function testConstructor() : void
    {
        $method = 'POST';
        $uri = '/foo?bar';
        $headers = ['X-Foo' => ['bar', 'baz'], 'X-Bar' => ['baz']];
        $body = (new StreamFactory)->createStreamFromResource(\STDOUT);
        $target = '/bar?baz';
        $protocol = '2.0';

        $mess = new Request(
            $method,
            $uri,
            $headers,
            $body,
            $target,
            $protocol
        );

        $this->assertSame($method, $mess->getMethod());
        $this->assertSame('/foo', $mess->getUri()->getPath());
        $this->assertSame('bar', $mess->getUri()->getQuery());
        $this->assertSame($headers, $mess->getHeaders());
        $this->assertSame($body, $mess->getBody());
        $this->assertSame($target, $mess->getRequestTarget());
        $this->assertSame($protocol, $mess->getProtocolVersion());
    }

    /**
     * @return void
     */
    public function testMethod() : void
    {
        $mess = new Request();
        $copy = $mess->withMethod('POST');

        $this->assertInstanceOf(Message::class, $copy);
        $this->assertInstanceOf(RequestInterface::class, $copy);
        $this->assertNotEquals($mess, $copy);

        $this->assertSame('GET', $mess->getMethod());
        
        $this->assertSame('POST', $copy->getMethod());
    }

    /**
     * @return void
     */
    public function testLowercasedMethod() : void
    {
        $mess = (new Request)->withMethod('post');

        $this->assertSame('POST', $mess->getMethod());
    }

    /**
     * @return void
     */
    public function testFigMethod($method) : void
    {
        $mess = (new Request)->withMethod($method);

        $this->assertSame($method, $mess->getMethod());
    }

    /**
     * @return void
     */
    public function testInvalidMethod($method) : void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Request)->withMethod($method);
    }

    /**
     * @return void
     */
    public function testRequestTarget() : void
    {
        $mess = new Request();
        $copy = $mess->withRequestTarget('/path?query');

        $this->assertInstanceOf(Message::class, $copy);
        $this->assertInstanceOf(RequestInterface::class, $copy);
        $this->assertNotEquals($mess, $copy);

        $this->assertSame('/', $mess->getRequestTarget());
        
        $this->assertSame('/path?query', $copy->getRequestTarget());
    }

    /**
     * @return void
     */
    public function testRequestTargetWithDifferentUriForms($requestTarget) : void
    {
        $mess = (new Request)->withRequestTarget($requestTarget);

        $this->assertSame($requestTarget, $mess->getRequestTarget());
    }

    /**
     * @return void
     */
    public function testInvalidRequestTarget($requestTarget) : void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Request)->withRequestTarget($requestTarget);
    }

    /**
     * @return void
     */
    public function testGetRequestTargetHavingUriWithoutPath() : void
    {
        $uri = (new UriFactory)->createUri('http://localhost');
        $mess = (new Request)->withUri($uri);

        $this->assertSame('/', $mess->getRequestTarget());
    }

    /**
     * @return void
     */
    public function testGetRequestTargetHavingUriWithNotAbsolutePath() : void
    {
        $uri = (new UriFactory)->createUri('not/absolute/path?query');
        $mess = (new Request)->withUri($uri);

        $this->assertSame('/', $mess->getRequestTarget());
    }

    /**
     * @return void
     */
    public function testGetRequestTargetHavingUriWithAbsolutePath() : void
    {
        $uri = (new UriFactory)->createUri('/path');
        $mess = (new Request)->withUri($uri);

        $this->assertSame('/path', $mess->getRequestTarget());
    }

    /**
     * @return void
     */
    public function testGetRequestTargetHavingUriWithAbsolutePathAndQuery() : void
    {
        $uri = (new UriFactory)->createUri('/path?query');
        $mess = (new Request)->withUri($uri);

        $this->assertSame('/path?query', $mess->getRequestTarget());
    }

    /**
     * @return void
     */
    public function testGetRequestTargetIgnoringNewUri() : void
    {
        $uri = (new UriFactory)->createUri('/new');
        $mess = (new Request)->withRequestTarget('/primary')->withUri($uri);

        $this->assertSame('/primary', $mess->getRequestTarget());
    }

    /**
     * @return void
     */
    public function testUri() : void
    {
        $uri = (new UriFactory)->createUri('/');
        $mess = new Request();
        $copy = $mess->withUri($uri);

        $this->assertInstanceOf(Message::class, $copy);
        $this->assertInstanceOf(RequestInterface::class, $copy);
        $this->assertNotEquals($mess, $copy);

        $this->assertNotSame($uri, $mess->getUri());
        
        $this->assertSame($uri, $copy->getUri());
    }

    /**
     * @return void
     */
    public function testUriWithAssigningHostHeaderFromUriHost() : void
    {
        $uri = (new UriFactory)->createUri('http://localhost');
        $mess = (new Request)->withUri($uri);

        $this->assertSame($uri->getHost(), $mess->getHeaderLine('host'));
    }

    /**
     * @return void
     */
    public function testUriWithAssigningHostHeaderFromUriHostAndPort() : void
    {
        $uri = (new UriFactory)->createUri('http://localhost:3000');
        $mess = (new Request)->withUri($uri);

        $this->assertSame($uri->getHost() . ':' . $uri->getPort(), $mess->getHeaderLine('host'));
    }

    /**
     * @return void
     */
    public function testUriWithReplacingHostHeaderFromUri() : void
    {
        $uri = (new UriFactory)->createUri('http://localhost');
        $mess = (new Request)->withHeader('host', 'example.com')->withUri($uri);

        $this->assertSame($uri->getHost(), $mess->getHeaderLine('host'));
    }

    /**
     * @return void
     */
    public function testUriWithPreservingHostHeader() : void
    {
        $uri = (new UriFactory)->createUri('http://localhost');
        $mess = (new Request)->withHeader('host', 'example.com')->withUri($uri, true);

        $this->assertSame('example.com', $mess->getHeaderLine('host'));
    }

    /**
     * @return void
     */
    public function testUriWithPreservingHostHeaderIfItIsEmpty() : void
    {
        $uri = (new UriFactory)->createUri('http://localhost');
        $mess = (new Request)->withUri($uri, true);

        $this->assertSame($uri->getHost(), $mess->getHeaderLine('host'));
    }

    /**
     * @return array
     */
    public function figMethodProvider() : array
    {
        return [
            ['HEAD'],
            ['GET'],
            ['POST'],
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
            ['PURGE'],
            ['OPTIONS'],
            ['TRACE'],
            ['CONNECT'],
        ];
    }

    /**
     * @return array
     */
    public function invalidMethodProvider() : array
    {
        return [
            [''],
            ["BAR\0BAZ"],
            ["BAR\tBAZ"],
            ["BAR\nBAZ"],
            ["BAR\rBAZ"],
            ["BAR BAZ"],
            ["BAR,BAZ"],

            [true],
            [false],
            [1],
            [1.1],
            [[]],
            [new \stdClass],
            [\STDOUT],
            [null],
            [function () {
            }],
        ];
    }

    /**
     * @return array
     */
    public function invalidRequestTargetProvider() : array
    {
        return [
            [''],
            ["/path\0/"],
            ["/path\t/"],
            ["/path\n/"],
            ["/path\r/"],
            ["/path /"],

            [true],
            [false],
            [1],
            [1.1],
            [[]],
            [new \stdClass],
            [\STDOUT],
            [null],
            [function () {
            }],
        ];
    }

    /**
     * @return array
     */
    public function uriFormProvider() : array
    {
        return [
            
            ['/path?query'],

            ['http://localhost/path?query'],

            ['localhost:3000'],

            ['*'],
        ];
    }
}
