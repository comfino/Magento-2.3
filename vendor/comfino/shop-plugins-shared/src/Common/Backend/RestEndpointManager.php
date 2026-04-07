<?php

declare(strict_types=1);

namespace Comfino\Common\Backend;

use Comfino\Api\Exception\AccessDenied;
use Comfino\Api\Exception\AuthorizationError;
use Comfino\Api\HttpErrorExceptionInterface;
use Comfino\Api\SerializerInterface;
use Comfino\Common\Exception\InvalidEndpoint;
use ComfinoExternal\Psr\Http\Message\ResponseFactoryInterface;
use ComfinoExternal\Psr\Http\Message\ResponseInterface;
use ComfinoExternal\Psr\Http\Message\ServerRequestFactoryInterface;
use ComfinoExternal\Psr\Http\Message\ServerRequestInterface;
use ComfinoExternal\Psr\Http\Message\StreamFactoryInterface;
use ComfinoExternal\Psr\Http\Message\UriFactoryInterface;

final class RestEndpointManager
{
    /**
     * @var string
     */
    protected $platformName;
    /**
     * @var string
     */
    protected $platformVersion;
    /**
     * @var string
     */
    protected $pluginVersion;
    /**
     * @var string[]
     */
    protected $apiKeys;
    /**
     * @var \ComfinoExternal\Psr\Http\Message\ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;
    /**
     * @var \ComfinoExternal\Psr\Http\Message\StreamFactoryInterface
     */
    protected $streamFactory;
    /**
     * @var \ComfinoExternal\Psr\Http\Message\UriFactoryInterface
     */
    protected $uriFactory;
    /**
     * @var \ComfinoExternal\Psr\Http\Message\ResponseFactoryInterface
     */
    protected $responseFactory;
    /**
     * @var \Comfino\Api\SerializerInterface
     */
    protected $serializer;
    /**
     * @var $this|null
     */
    private static $instance;

    private $registeredEndpoints = [];

    /**
     * @var string|null
     */
    private $crSignature;
    /**
     * @var string|null
     */
    private $calculatedCrSignature;

    /**
     * @param string $platformName
     * @param string $platformVersion
     * @param string $pluginVersion
     * @param string[] $apiKeys
     * @param ServerRequestFactoryInterface $serverRequestFactory
     * @param StreamFactoryInterface $streamFactory
     * @param UriFactoryInterface $uriFactory
     * @param ResponseFactoryInterface $responseFactory
     * @param SerializerInterface $serializer
     * @return self
     */
    public static function getInstance(
        string $platformName,
        string $platformVersion,
        string $pluginVersion,
        array $apiKeys,
        ServerRequestFactoryInterface $serverRequestFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
        ResponseFactoryInterface $responseFactory,
        SerializerInterface $serializer
    ): self {
        if (self::$instance === null) {
            self::$instance = new self(
                $platformName,
                $platformVersion,
                $pluginVersion,
                $apiKeys,
                $serverRequestFactory,
                $streamFactory,
                $uriFactory,
                $responseFactory,
                $serializer
            );
        }

        return self::$instance;
    }

    /**
     * @param string[] $apiKeys
     */
    private function __construct(string $platformName, string $platformVersion, string $pluginVersion, array $apiKeys, ServerRequestFactoryInterface $serverRequestFactory, StreamFactoryInterface $streamFactory, UriFactoryInterface $uriFactory, ResponseFactoryInterface $responseFactory, SerializerInterface $serializer)
    {
        $this->platformName = $platformName;
        $this->platformVersion = $platformVersion;
        $this->pluginVersion = $pluginVersion;
        $this->apiKeys = $apiKeys;
        $this->serverRequestFactory = $serverRequestFactory;
        $this->streamFactory = $streamFactory;
        $this->uriFactory = $uriFactory;
        $this->responseFactory = $responseFactory;
        $this->serializer = $serializer;
    }

    /**
     * @param RestEndpointInterface $endpoint
     */
    public function registerEndpoint(RestEndpointInterface $endpoint): void
    {
        $this->registeredEndpoints[$endpoint->getName()] = $endpoint;
        $this->registeredEndpoints[$endpoint->getName()]->setSerializer($this->serializer);
    }

    /**
     * @param string $name
     * @return RestEndpointInterface|null
     */
    public function getEndpointByName(string $name): ?RestEndpointInterface
    {
        return $this->registeredEndpoints[$name] ?? null;
    }

    /**
     * @return string|null
     */
    public function getReceivedCrSignature(): ?string
    {
        return $this->crSignature;
    }

    /**
     * @return string|null
     */
    public function getCalculatedCrSignature(): ?string
    {
        return $this->calculatedCrSignature;
    }

    /**
     * @return string
     * @throws RandomException
     */
    public function getValidationKey(): string
    {
        return bin2hex(random_bytes(10));
    }

    /**
     * @param string $requestData
     * @return string
     */
    public function getCrSignature(string $requestData): string
    {
        return hash('sha3-256', $this->apiKeys[0] . $requestData);
    }

    /**
     * @return array<string,
     */
    public function getRegisteredEndpoints(): array
    {
        $endpoints = [];

        foreach ($this->registeredEndpoints as $endpoint) {
            $endpoints[(new \ReflectionClass($endpoint))->getShortName()] = [
                'url' => $endpoint->getEndpointUrl(),
                'methods' => $endpoint->getMethods(),
            ];
        }

        return $endpoints;
    }

    /**
     * @param string|null $endpointName
     * @param ServerRequestInterface|null $serverRequest
     * @return ResponseInterface
     */
    public function processRequest(?string $endpointName = null, ?ServerRequestInterface $serverRequest = null): ResponseInterface
    {
        if ($serverRequest === null) {
            $serverRequest = $this->getServerRequest();
        }

        try {
            $this->verifyRequest($serverRequest);
        } catch (HttpErrorExceptionInterface $e) {
            return $this->getPreparedResponse($this->responseFactory->createResponse($e->getStatusCode(), $e->getMessage()));
        }

        if (($endpointName !== null) && ($endpoint = $this->getEndpointByName($endpointName)) !== null) {
            try {
                return $this->prepareResponse($serverRequest, $endpoint->processRequest($serverRequest, $endpointName));
            } catch (HttpErrorExceptionInterface $e) {
                return $this->getPreparedResponse(
                    $this->responseFactory->createResponse($e->getStatusCode(), $e->getMessage()),
                    ['error' => $e->getMessage()]
                );
            }
        }

        foreach ($this->registeredEndpoints as $endpoint) {
            try {
                return $this->prepareResponse($serverRequest, $endpoint->processRequest($serverRequest));
            } catch (InvalidEndpoint $exception) {
                continue;
            } catch (HttpErrorExceptionInterface $e) {
                return $this->getPreparedResponse(
                    $this->responseFactory->createResponse($e->getStatusCode(), $e->getMessage()),
                    ['error' => $e->getMessage()]
                );
            }
        }

        return $this->getPreparedResponse($this->responseFactory->createResponse(404, 'Endpoint not found.'));
    }

    /**
     * @return ServerRequestInterface
     */
    public function getServerRequest(): ServerRequestInterface
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (array_key_exists('HTTPS', $_SERVER) && 'off' !== $_SERVER['HTTPS']) {
            $scheme = 'https://';
        } else {
            $scheme = 'http://';
        }

        if (array_key_exists('HTTP_HOST', $_SERVER)) {
            $host = $_SERVER['HTTP_HOST'];
        } elseif (array_key_exists('SERVER_NAME', $_SERVER)) {
            $host = $_SERVER['SERVER_NAME'];

            if (array_key_exists('SERVER_PORT', $_SERVER)) {
                $host .= (':' . $_SERVER['SERVER_PORT']);
            }
        } else {
            $host = 'localhost';
        }

        if (array_key_exists('REQUEST_URI', $_SERVER)) {
            $target = $_SERVER['REQUEST_URI'];
        } elseif (array_key_exists('PHP_SELF', $_SERVER)) {
            $target = $_SERVER['PHP_SELF'];

            if (array_key_exists('QUERY_STRING', $_SERVER)) {
                $target .= ('?' . $_SERVER['QUERY_STRING']);
            }
        } else {
            $target = '/';
        }

        $serverRequest = $this->serverRequestFactory->createServerRequest(
            $requestMethod,
            $this->uriFactory->createUri($scheme . $host . $target),
            $_SERVER
        );

        if ($requestMethod === 'POST' || $requestMethod === 'PUT' || $requestMethod === 'PATCH') {
            $input = fopen('php://input', 'rb');
            $resource = fopen('php://temp', 'r+b');

            stream_copy_to_stream($input, $resource);
            rewind($resource);

            $bodyStream = $this->streamFactory->createStreamFromResource($resource);

            if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                rewind($input);

                if (!empty($body = stream_get_contents($input))) {
                    $parsedBody = $this->serializer->unserialize($body);
                }
            } else {
                $parsedBody = $_POST;
            }

            fclose($input);
        }

        if (isset($bodyStream)) {
            return isset($parsedBody)
                ? $serverRequest->withQueryParams($_GET)->withBody($bodyStream)->withParsedBody($parsedBody)
                : $serverRequest->withQueryParams($_GET)->withBody($bodyStream);
        }

        return $serverRequest->withQueryParams($_GET);
    }

    /**
     * @throws AuthorizationError
     * @throws AccessDenied
     */
    protected function verifyRequest(ServerRequestInterface $request): void
    {
        $this->crSignature = $request->hasHeader('CR-Signature') ? $request->getHeader('CR-Signature')[0] : null;

        if (empty($this->crSignature) && $request->hasHeader('X-CR-Signature')) {
            $this->crSignature = $request->getHeader('X-CR-Signature')[0] ?? null;
        }

        if (empty($this->crSignature)) {
            throw new AuthorizationError('Unauthorized request.');
        }

        $requestAuthorized = false;

        if (strtoupper($request->getMethod()) === 'GET') {
            if (!isset($request->getQueryParams()['vkey'])) {
                throw new AuthorizationError('Unauthorized request.');
            }

            $validationKey = $request->getQueryParams()['vkey'];

            foreach ($this->apiKeys as $apiKey) {
                $this->calculatedCrSignature = hash('sha3-256', $apiKey . $validationKey);

                if (hash_equals($this->calculatedCrSignature, $this->crSignature)) {
                    $requestAuthorized = true;

                    break;
                }
            }
        } else {
            $requestBody = $request->getBody()->getContents();

            $request->getBody()->rewind();

            foreach ($this->apiKeys as $apiKey) {
                $this->calculatedCrSignature = hash('sha3-256', $apiKey . $requestBody);

                if (hash_equals($this->calculatedCrSignature, $this->crSignature)) {
                    $requestAuthorized = true;

                    break;
                }
            }
        }

        if (!$requestAuthorized) {
            throw new AccessDenied('Access not allowed. Failed comparison of CR-Signature and shop hash.');
        }
    }

    protected function prepareResponse(ServerRequestInterface $serverRequest, ?array $responseBody): ResponseInterface
    {
        switch (strtoupper($serverRequest->getMethod())) {
            case 'GET':
                return $this->getPreparedResponse(
                    $this->responseFactory->createResponse(200, 'OK'),
                    $responseBody
                );

            case 'POST':
                return $this->getPreparedResponse(
                    $this->responseFactory->createResponse(201, 'Created'),
                    $responseBody
                );

            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                return empty($responseBody)
                    ? $this->getPreparedResponse($this->responseFactory->createResponse(204, 'No content'))
                    : $this->getPreparedResponse($this->responseFactory->createResponse(200, 'OK'), $responseBody);
        }

        return $this->getPreparedResponse($this->responseFactory->createResponse(404, 'Endpoint not found.'));
    }

    protected function getPreparedResponse(ResponseInterface $response, ?array $responseData = null): ResponseInterface
    {
        $pluginHeader = "$this->platformName $this->platformVersion, Comfino $this->pluginVersion";

        if ($responseData !== null) {
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Comfino-Plugin', $pluginHeader)
                ->withBody($this->streamFactory->createStream($this->serializer->serialize($responseData)));
        }

        return $response->withHeader('Comfino-Plugin', $pluginHeader);
    }
}
