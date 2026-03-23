<?php

declare(strict_types=1);

namespace Comfino\Tests;

use Comfino\Api\Client;
use Comfino\Extended\Api\Client as ExtendedClient;
use Comfino\Extended\Api\Dto\Plugin\ShopPluginError;
use Comfino\Extended\Api\Serializer\Json as JsonSerializer;
use ComfinoExternal\Psr\Http\Message\RequestInterface;
use ComfinoExternal\Psr\Http\Message\ResponseInterface;
use ComfinoExternal\Sunrise\Http\Factory\RequestFactory;
use ComfinoExternal\Sunrise\Http\Factory\ResponseFactory;
use ComfinoExternal\Sunrise\Http\Factory\StreamFactory;
use Http\Message\RequestMatcher\RequestMatcher;

trait ExtendedClientTestTrait
{
    use ReflectionTrait;

    /**
     * @var string
     */
    protected $productionApiHost;

    public function testSendLoggedError(): void
    {
        $shopPluginError = new ShopPluginError(
            'host',
            'platform',
            ['attrib1' => 'value1', 'attrib2' => 'value2'],
            '123EF',
            'Error message.'
        );
        $hashKey = 'Comfino API client ' . Client::CLIENT_VERSION;
        $errorDetails = gzcompress('{"host":"host","platform":"platform","environment":{"attrib1":"value1","attrib2":"value2"},"error_code":"123EF","error_message":"Error message.","api_request_url":null,"api_request":null,"api_response":null,"stack_trace":null}', 9);
        $encodedErrorDetails = base64_encode($errorDetails);

        $request = [
            'error_details' => $encodedErrorDetails,
            'hash' => hash_hmac('sha3-256', $encodedErrorDetails, $hashKey),
        ];

        $apiClient = $this->initApiClient('/v1/log-plugin-error', 'POST', null, (new JsonSerializer())->serialize($request), null, 'API-KEY');

        $this->assertTrue($apiClient->sendLoggedError($shopPluginError));
    }

    public function testSendLoggedErrorFailure(): void
    {
        $shopPluginError = new ShopPluginError(
            'host',
            'platform',
            ['attrib1' => 'value1', 'attrib2' => 'value2'],
            '123EF',
            'Error message.'
        );
        $hashKey = 'Comfino API client ' . Client::CLIENT_VERSION;
        $errorDetails = gzcompress('{"host":"host","platform":"platform","environment":{"attrib1":"value1","attrib2":"value2"},"error_code":"123EF","error_message":"Error message.","api_request_url":null,"api_request":null,"api_response":null,"stack_trace":null}', 9);
        $encodedErrorDetails = base64_encode($errorDetails);

        $request = [
            'error_details' => $encodedErrorDetails,
            'hash' => hash_hmac('sha3-256', $encodedErrorDetails, $hashKey),
        ];

        $apiClient = $this->initApiClient('/v1/log-plugin-error', 'POST', null, (new JsonSerializer())->serialize($request), null, 'API-KEY', false, 500);

        $this->assertFalse($apiClient->sendLoggedError($shopPluginError));
    }

    public function testNotifyPluginRemoval(): void
    {
        $apiClient = $this->initApiClient('/v1/log-plugin-remove', 'PUT', null, null, null, 'API-KEY');

        $this->assertTrue($apiClient->notifyPluginRemoval());
    }

    public function testNotifyPluginRemovalFailure(): void
    {
        $apiClient = $this->initFailingApiClient();

        $this->assertFalse($apiClient->notifyPluginRemoval());
    }

    public function testNotifyAbandonedCart(): void
    {
        $requestBody = (new JsonSerializer())->serialize(['type' => 'cart_created']);
        $apiClient = $this->initApiClient('/v1/abandoned_cart', 'POST', null, $requestBody, null, 'API-KEY');

        $this->assertTrue($apiClient->notifyAbandonedCart('cart_created'));
    }

    public function testNotifyAbandonedCartFailure(): void
    {
        $apiClient = $this->initFailingApiClient();

        $this->assertFalse($apiClient->notifyAbandonedCart('cart_updated'));
    }

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->productionApiHost = parse_url($this->getConstantFromClass(Client::class, 'PRODUCTION_HOST'), PHP_URL_HOST);
    }

    private function initApiClient(string $endpointPath, string $method, ?array $queryParameters = null, ?string $requestBody = null, $responseData = null, ?string $apiKey = null, bool $isPublicEndpoint = false, int $responseStatus = 200): ExtendedClient
    {
        $client = new \Http\Mock\Client();
        $client->on(
            new RequestMatcher($endpointPath, $this->productionApiHost, $method, 'https'),
            function (RequestInterface $request) use ($queryParameters, $requestBody, $responseData, $apiKey, $isPublicEndpoint, $responseStatus) {
                return $this->processRequest($request, $queryParameters, $requestBody, $responseData, $apiKey, $isPublicEndpoint, $responseStatus);
            }
        );

        return new ExtendedClient(new RequestFactory(), new StreamFactory(), $client, $apiKey);
    }

    private function initFailingApiClient(): ExtendedClient
    {
        $client = new \Http\Mock\Client();
        $client->on(
            new RequestMatcher(null, null, null, 'https'),
            function (RequestInterface $request) {
                throw new \RuntimeException('Simulated network error');
            }
        );

        return new ExtendedClient(new RequestFactory(), new StreamFactory(), $client, 'API-KEY');
    }

    private function processRequest(RequestInterface $request, ?array $queryParameters = null, ?string $requestBody = null, $responseData = null, ?string $apiKey = null, bool $isPublicEndpoint = false, int $responseStatus = 200): ResponseInterface
    {
        if (!$isPublicEndpoint && (!$request->hasHeader('Api-Key') || $request->getHeaderLine('Api-Key') !== $apiKey)) {
            return (new ResponseFactory())->createJsonResponse(401, ['message' => 'Invalid credentials.']);
        }

        if ($requestBody !== null) {
            $this->assertEquals($requestBody, $request->getBody()->getContents(), 'Request body is invalid.');
        } else {
            $this->assertEquals('', $request->getBody()->getContents(), 'Request body is invalid.');
        }

        if (is_array($queryParameters) && count($queryParameters)) {
            $this->assertEquals(http_build_query($queryParameters), $request->getUri()->getQuery(), 'Request URL query string is invalid.');
        } else {
            $this->assertEquals('', $request->getUri()->getQuery(), 'Request URL query string is invalid.');
        }

        return (new ResponseFactory())->createJsonResponse($responseStatus, $responseData);
    }
}
