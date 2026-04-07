<?php

declare(strict_types=1);

namespace Comfino\Api;

use Comfino\Api\Dto\Payment\LoanQueryCriteria;
use Comfino\Api\Dto\Payment\LoanTypeEnum;
use Comfino\Api\Exception\AccessDenied;
use Comfino\Api\Exception\AuthorizationError;
use Comfino\Api\Exception\RequestValidationError;
use Comfino\Api\Exception\ResponseValidationError;
use Comfino\Api\Exception\ServiceUnavailable;
use Comfino\Api\Request\CancelOrder as CancelOrderRequest;
use Comfino\Api\Request\CreateOrder as CreateOrderRequest;
use Comfino\Api\Request\GetFinancialProductDetails as GetFinancialProductDetailsRequest;
use Comfino\Api\Request\GetFinancialProducts as GetFinancialProductsRequest;
use Comfino\Api\Request\GetOrder as GetOrderRequest;
use Comfino\Api\Request\GetPaywall as GetPaywallRequest;
use Comfino\Api\Request\GetPaywallItemDetails as GetPaywallItemDetailsRequest;
use Comfino\Api\Request\GetProductTypes as GetProductTypesRequest;
use Comfino\Api\Request\GetWidgetKey as GetWidgetKeyRequest;
use Comfino\Api\Request\GetWidgetTypes as GetWidgetTypesRequest;
use Comfino\Api\Request\IsShopAccountActive as IsShopAccountActiveRequest;
use Comfino\Api\Response\Base as BaseApiResponse;
use Comfino\Api\Response\CreateOrder as CreateOrderResponse;
use Comfino\Api\Response\GetFinancialProductDetails as GetFinancialProductDetailsResponse;
use Comfino\Api\Response\GetFinancialProducts as GetFinancialProductsResponse;
use Comfino\Api\Response\GetOrder as GetOrderResponse;
use Comfino\Api\Response\GetPaywall as GetPaywallResponse;
use Comfino\Api\Response\GetPaywallItemDetails as GetPaywallItemDetailsResponse;
use Comfino\Api\Response\GetProductTypes as GetProductTypesResponse;
use Comfino\Api\Response\GetWidgetKey as GetWidgetKeyResponse;
use Comfino\Api\Response\GetWidgetTypes as GetWidgetTypesResponse;
use Comfino\Api\Response\IsShopAccountActive as IsShopAccountActiveResponse;
use Comfino\Api\Response\ValidateOrder as ValidateOrderResponse;
use Comfino\Api\Serializer\Json as JsonSerializer;
use Comfino\Shop\Order\CartInterface;
use Comfino\Shop\Order\OrderInterface;
use ComfinoExternal\Psr\Http\Client\ClientExceptionInterface;
use ComfinoExternal\Psr\Http\Client\ClientInterface;
use ComfinoExternal\Psr\Http\Message\RequestFactoryInterface;
use ComfinoExternal\Psr\Http\Message\ResponseInterface;
use ComfinoExternal\Psr\Http\Message\StreamFactoryInterface;

/**
 * Comfino API client.
 *
 * @version 1.1.2
 * @author Artur Kozubski <akozubski@comperia.pl>
 */
class Client
{
    /**
     * @var RequestFactoryInterface
     */
    protected $requestFactory;
    /**
     * @var StreamFactoryInterface
     */
    protected $streamFactory;
    /**
     * @var ClientInterface
     */
    protected $client;
    /**
     * @var string|null
     */
    protected $apiKey;
    /**
     * @var int
     */
    protected $apiVersion = 1;
    /**
     * @var SerializerInterface|null
     */
    protected $serializer;
    public const CLIENT_VERSION = '1.1.2';
    public const PRODUCTION_HOST = 'https://api-ecommerce.comfino.pl';
    public const SANDBOX_HOST = 'https://api-ecommerce.craty.pl';

    protected $apiLanguage = 'pl';

    protected $apiCurrency = 'PLN';

    protected $customApiHost;

    protected $customUserAgent;

    protected $customHeaders = [];

    protected $clientHostName = '';

    protected $isSandboxMode = false;

    protected $request;

    protected $response;

    /**
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface $streamFactory
     * @param ClientInterface $client
     * @param string|null $apiKey
     * @param int $apiVersion
     * @param SerializerInterface|null $serializer
     */
    public function __construct(RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory, ClientInterface $client, ?string $apiKey, int $apiVersion = 1, ?SerializerInterface $serializer = null)
    {
        $serializer = $serializer ?? null ?? new JsonSerializer();
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->apiVersion = $apiVersion;
        $this->serializer = $serializer;
    }

    /**
     * @param SerializerInterface $serializer
     * @return void
     */
    public function setSerializer($serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * @param int $version
     * @return void
     */
    public function setApiVersion($version): void
    {
        $this->apiVersion = $version;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey ?? '';
    }

    /**
     * @param string $apiKey
     * @return void
     */
    public function setApiKey($apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiLanguage(): string
    {
        return $this->apiLanguage;
    }

    /**
     * @param string $language
     * @return void
     */
    public function setApiLanguage($language): void
    {
        $this->apiLanguage = $language;
    }

    /**
     * @return string
     */
    public function getApiCurrency(): string
    {
        return $this->apiCurrency;
    }

    /**
     * @param string $apiCurrency
     * @return void
     */
    public function setApiCurrency($apiCurrency): void
    {
        $this->apiCurrency = $apiCurrency;
    }

    /**
     * @return string
     */
    public function getApiHost(): string
    {
        return $this->customApiHost ?? ($this->isSandboxMode ? self::SANDBOX_HOST : self::PRODUCTION_HOST);
    }

    /**
     * @param string|null $host
     * @return void
     */
    public function setCustomApiHost($host): void
    {
        $this->customApiHost = $host;
    }

    /**
     * @param string|null $userAgent
     * @return void
     */
    public function setCustomUserAgent($userAgent): void
    {
        $this->customUserAgent = $userAgent;
    }

    /**
     * @param string $headerName
     * @param string $headerValue
     * @return void
     */
    public function addCustomHeader($headerName, $headerValue): void
    {
        $this->customHeaders[$headerName] = $headerValue;
    }

    /**
     * @param string $host
     * @return void
     */
    public function setClientHostName($host): void
    {
        if (($filteredHost = filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) === false) {
            $filteredHost = gethostname();
        }

        $this->clientHostName = $filteredHost !== false ? $filteredHost : '';
    }

    public function enableSandboxMode(): void
    {
        $this->isSandboxMode = true;
    }

    public function disableSandboxMode(): void
    {
        $this->isSandboxMode = false;
    }

    /**
     * @param \ComfinoExternal\Psr\Http\Client\ClientInterface $client
     */
    public function setClient($client): void
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return self::CLIENT_VERSION;
    }

    /**
     * @return Request|null
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * @param string|null $cacheInvalidateUrl
     * @param string|null $configurationUrl
     * @return bool
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     * @throws ClientExceptionInterface
     */
    public function isShopAccountActive($cacheInvalidateUrl = null, $configurationUrl = null): bool
    {
        $this->request = (new IsShopAccountActiveRequest($cacheInvalidateUrl, $configurationUrl))->setSerializer($this->serializer);

        return (new IsShopAccountActiveResponse($this->request, $this->sendRequest($this->request), $this->serializer))->isActive;
    }

    /**
     * @param LoanQueryCriteria $queryCriteria
     * @param CartInterface $cart
     * @return GetFinancialProductDetailsResponse
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     * @throws ClientExceptionInterface
     */
    public function getFinancialProductDetails($queryCriteria, $cart): GetFinancialProductDetailsResponse
    {
        $this->request = (new GetFinancialProductDetailsRequest($queryCriteria, $cart))->setSerializer($this->serializer);

        return new GetFinancialProductDetailsResponse($this->request, $this->sendRequest($this->request), $this->serializer);
    }

    /**
     * @param LoanQueryCriteria $queryCriteria
     * @return GetFinancialProductsResponse
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     * @throws ClientExceptionInterface
     */
    public function getFinancialProducts($queryCriteria): GetFinancialProductsResponse
    {
        $this->request = (new GetFinancialProductsRequest($queryCriteria))->setSerializer($this->serializer);

        return new GetFinancialProductsResponse($this->request, $this->sendRequest($this->request), $this->serializer);
    }

    /**
     * @param OrderInterface $order
     * @return CreateOrderResponse
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     * @throws ClientExceptionInterface
     */
    public function createOrder($order): CreateOrderResponse
    {
        $this->request = (new CreateOrderRequest($order, $this->apiKey ?? ''))->setSerializer($this->serializer);

        return new CreateOrderResponse($this->request, $this->sendRequest($this->request), $this->serializer);
    }

    /**
     * @param OrderInterface $order
     * @return ValidateOrderResponse
     */
    public function validateOrder($order): ValidateOrderResponse
    {
        try {
            $this->request = (new CreateOrderRequest($order, $this->apiKey ?? '', true))->setSerializer($this->serializer);

            return new ValidateOrderResponse($this->request, $this->sendRequest($this->request), $this->serializer);
        } catch (\Throwable $e) {
            return new ValidateOrderResponse(
                $this->request,
                $e instanceof RequestValidationError ? $e->getResponse() : $this->response,
                $this->serializer,
                $e
            );
        }
    }

    /**
     * @param string $orderId
     * @return GetOrderResponse
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     * @throws ClientExceptionInterface
     */
    public function getOrder($orderId): GetOrderResponse
    {
        $this->request = (new GetOrderRequest($orderId))->setSerializer($this->serializer);

        return new GetOrderResponse($this->request, $this->sendRequest($this->request), $this->serializer);
    }

    /**
     * @param string $orderId
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     * @throws ClientExceptionInterface
     */
    public function cancelOrder($orderId): void
    {
        $this->request = (new CancelOrderRequest($orderId))->setSerializer($this->serializer);

        new BaseApiResponse($this->request, $this->sendRequest($this->request), $this->serializer);
    }

    /**
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     * @throws ClientExceptionInterface
     * @param \Comfino\FinancialProduct\ProductTypesListTypeEnum $listType
     */
    public function getProductTypes($listType): GetProductTypesResponse
    {
        $this->request = (new GetProductTypesRequest($listType))->setSerializer($this->serializer);

        return new GetProductTypesResponse($this->request, $this->sendRequest($this->request), $this->serializer);
    }

    /**
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     * @throws ClientExceptionInterface
     */
    public function getWidgetKey(): string
    {
        $this->request = (new GetWidgetKeyRequest())->setSerializer($this->serializer);

        return (new GetWidgetKeyResponse($this->request, $this->sendRequest($this->request), $this->serializer))->widgetKey;
    }

    /**
     * @param bool $useNewApi
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     * @throws ClientExceptionInterface
     */
    public function getWidgetTypes($useNewApi = true): GetWidgetTypesResponse
    {
        $this->request = (new GetWidgetTypesRequest($useNewApi))->setSerializer($this->serializer);

        return new GetWidgetTypesResponse($this->request, $this->sendRequest($this->request), $this->serializer);
    }

    /**
     * @param LoanQueryCriteria $queryCriteria
     * @param string|null $recalculationUrl
     * @return GetPaywallResponse
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     * @throws ClientExceptionInterface
     */
    public function getPaywall($queryCriteria, $recalculationUrl = null): GetPaywallResponse
    {
        $this->request = (new GetPaywallRequest($queryCriteria, $recalculationUrl))->setSerializer($this->serializer);

        return new GetPaywallResponse($this->request, $this->sendRequest($this->request, 2), $this->serializer);
    }

    /**
     * @param int $loanAmount
     * @param LoanTypeEnum $loanType
     * @param CartInterface $cart
     * @return GetPaywallItemDetailsResponse
     * @throws RequestValidationError
     * @throws ResponseValidationError
     * @throws AuthorizationError
     * @throws AccessDenied
     * @throws ServiceUnavailable
     * @throws ClientExceptionInterface
     */
    public function getPaywallItemDetails($loanAmount, $loanType, $cart): GetPaywallItemDetailsResponse
    {
        $this->request = (new GetPaywallItemDetailsRequest($loanAmount, $loanType, $cart))->setSerializer($this->serializer);

        return new GetPaywallItemDetailsResponse($this->request, $this->sendRequest($this->request), $this->serializer);
    }

    /**
     * @throws RequestValidationError
     * @throws ClientExceptionInterface
     * @param \Comfino\Api\Request $request
     * @param int|null $apiVersion
     */
    protected function sendRequest($request, $apiVersion = null): ResponseInterface
    {
        if (($trackId = !empty($this->clientHostName) ? $this->clientHostName : gethostname()) === false) {
            $trackId = 'trid-' . uniqid('', true);
        } else {
            $trackId .= ('-' . microtime(true));
        }

        $apiRequest = $request->getPsrRequest(
            $this->requestFactory,
            $this->streamFactory,
            $this->getApiHost(),
            $apiVersion ?? $this->apiVersion
        )
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Api-Language', $this->apiLanguage)
        ->withHeader('Api-Currency', $this->apiCurrency)
        ->withHeader('User-Agent', $this->getUserAgent())
        ->withHeader('Comfino-Track-Id', $trackId);

        if (count($this->customHeaders) > 0) {
            foreach ($this->customHeaders as $headerName => $headerValue) {
                $apiRequest = $apiRequest->withHeader($headerName, $headerValue);
            }
        }

        $this->response = $this->client->sendRequest(
            !empty($this->apiKey) ? $apiRequest->withHeader('Api-Key', $this->apiKey) : $apiRequest
        );

        return $this->response;
    }

    protected function getUserAgent(): string
    {
        return $this->customUserAgent ?? "Comfino API client {$this->getVersion()}";
    }
}
