<?php

declare(strict_types=1);

namespace Comfino\Common\Backend;

use Comfino\Api\SerializerInterface;
use Comfino\Common\Exception\InvalidEndpoint;
use Comfino\Common\Exception\InvalidRequest;
use ComfinoExternal\Psr\Http\Message\ServerRequestInterface;

abstract class RestEndpoint implements RestEndpointInterface
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $endpointUrl;
    /**
     * @var string[]
     */
    protected $methods;

    /**
     * @var SerializerInterface|null
     */
    protected $serializer;

    /**
     * @param string $name
     * @param string $endpointUrl
     */
    public function __construct(string $name, string $endpointUrl)
    {
        $this->name = $name;
        $this->endpointUrl = $endpointUrl;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getEndpointUrl(): string
    {
        return $this->endpointUrl;
    }

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer($serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * @param ServerRequestInterface $serverRequest
     * @param string|null $endpointName
     * @return bool
     */
    protected function endpointPathMatch($serverRequest, $endpointName = null): bool
    {
        $requestMethod = strtoupper($serverRequest->getMethod());

        if ($endpointName !== null && $endpointName === $this->name && in_array($requestMethod, $this->methods, true)) {
            return true;
        }

        return (string) $serverRequest->getUri() === $this->endpointUrl && in_array($requestMethod, $this->methods, true);
    }

    /**
     * @param ServerRequestInterface $serverRequest
     * @return array|string|null
     * @throws \JsonException
     */
    protected function getParsedRequestBody($serverRequest)
    {
        $contentType = $serverRequest->hasHeader('Content-Type') ? $serverRequest->getHeader('Content-Type')[0] : '';
        $requestPayload = $serverRequest->getBody()->getContents();

        $serverRequest->getBody()->rewind();

        if ($contentType === 'application/json') {
            if ($this->serializer !== null) {
                return $this->serializer->unserialize($requestPayload);
            }

            return json_decode($requestPayload, true, 512, 0);
        }

        if (strtoupper($serverRequest->getMethod()) === 'POST') {
            return $serverRequest->getParsedBody();
        }

        return $requestPayload;
    }

    /**
     * @param \ComfinoExternal\Psr\Http\Message\ServerRequestInterface $serverRequest
     * @param string|null $endpointName
     */
    public function processRequest($serverRequest, $endpointName = null): ?array
    {
        if (!$this->endpointPathMatch($serverRequest, $endpointName)) {
            throw new InvalidEndpoint('Endpoint path does not match request path.');
        }

        try {
            if (!is_array($requestPayload = $this->getParsedRequestBody($serverRequest))) {
                throw new InvalidRequest(
                    (string) $serverRequest->getUri(),
                    $serverRequest->getBody()->getContents(),
                    'Invalid request payload.'
                );
            }
        } catch (\JsonException $e) {
            throw new InvalidRequest(
                (string) $serverRequest->getUri(),
                $serverRequest->getBody()->getContents(),
                sprintf('Invalid request payload: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        return $requestPayload;
    }
}
