<?php

declare(strict_types=1);

namespace Comfino\Common\Api;

use Comfino\Api\Exception\RequestValidationError;
use Comfino\Api\Request;
use Comfino\Api\Request\CreateOrder as CreateOrderRequest;
use Comfino\Common\Api\Response\ValidateOrder as ValidateOrderResponse;
use Comfino\Common\Exception\ConnectionTimeout;
use ComfinoExternal\Psr\Http\Client\ClientExceptionInterface;
use ComfinoExternal\Psr\Http\Message\ResponseInterface;
use ComfinoExternal\Sunrise\Http\Factory\RequestFactory;
use ComfinoExternal\Sunrise\Http\Factory\ResponseFactory;
use ComfinoExternal\Sunrise\Http\Factory\StreamFactory;

class Client extends \Comfino\Extended\Api\Client
{
    /**
     * @var int
     */
    protected $connectionTimeout = 1;
    /**
     * @var int
     */
    protected $transferTimeout = 3;
    /**
     * @var int
     */
    protected $connectionMaxNumAttempts = 3;
    /**
     * @var array
     */
    protected $options = [];

    private const MAX_CONNECTION_TIMEOUT = 30;

    private const MAX_TRANSFER_TIMEOUT = 60;

    private const DEFAULT_MAX_ATTEMPTS = 3;

    /**
     * @var \ComfinoExternal\Sunrise\Http\Factory\ResponseFactory
     */
    protected static $responseFactory;

    /**
     * @param string|null $apiKey
     * @param int $connectionTimeout
     * @param int $transferTimeout
     * @param int $connectionMaxNumAttempts
     * @param array $options
     */
    public function __construct(
        ?string $apiKey,
        int $connectionTimeout = 1,
        int $transferTimeout = 3,
        int $connectionMaxNumAttempts = 3,
        array $options = []
    ) {
        $this->connectionTimeout = $connectionTimeout;
        $this->transferTimeout = $transferTimeout;
        $this->connectionMaxNumAttempts = $connectionMaxNumAttempts;
        $this->options = $options;

        if ($this->connectionTimeout >= $this->transferTimeout) {
            $this->transferTimeout = 3 * $this->connectionTimeout;
        }

        if ($this->connectionMaxNumAttempts === 0) {
            $this->connectionMaxNumAttempts = self::DEFAULT_MAX_ATTEMPTS;
        }

        self::$responseFactory = new ResponseFactory();

        parent::__construct(
            new RequestFactory(),
            new StreamFactory(),
            $this->createClient($this->connectionTimeout, $this->transferTimeout, $this->options),
            $apiKey
        );
    }

    /**
     * @param \Comfino\Shop\Order\OrderInterface $order
     */
    public function validateOrder($order): \Comfino\Api\Response\ValidateOrder
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
     * @param int $connectionTimeout
     * @param int $transferTimeout
     * @param int $connectionMaxNumAttempts
     * @param array $options
     */
    public function resetClient($connectionTimeout, $transferTimeout, $connectionMaxNumAttempts, $options = []): void
    {
        $this->connectionMaxNumAttempts = $connectionMaxNumAttempts;

        sort($this->options);
        sort($options);

        if ($this->connectionTimeout === $connectionTimeout && $this->transferTimeout === $transferTimeout && $this->options === $options) {
            return;
        }

        $this->connectionTimeout = $connectionTimeout;
        $this->transferTimeout = $transferTimeout;
        $this->options = $options;

        if ($this->connectionTimeout >= $this->transferTimeout) {
            $this->transferTimeout = 3 * $this->connectionTimeout;
        }

        $this->client = $this->createClient($connectionTimeout, $transferTimeout, $options);
    }

    /**
     * @param int $connectAttemptIdx
     * @return int
     */
    public function calculateConnectionTimeout($connectAttemptIdx): int
    {
        if ($connectAttemptIdx < 1 || $connectAttemptIdx > $this->connectionMaxNumAttempts) {
            return $this->connectionTimeout;
        }

        if ($this->connectionMaxNumAttempts <= 1) {
            return $this->connectionTimeout;
        }

        $timeout = $this->connectionTimeout << ($connectAttemptIdx - 1);

        return min($timeout, self::MAX_CONNECTION_TIMEOUT);
    }

    /**
     * @param int $connectAttemptIdx
     * @return int
     */
    public function calculateTransferTimeout($connectAttemptIdx): int
    {
        if ($connectAttemptIdx < 1 || $connectAttemptIdx > $this->connectionMaxNumAttempts) {
            return $this->transferTimeout;
        }

        if ($this->connectionMaxNumAttempts <= 1) {
            return $this->transferTimeout;
        }

        $timeout = $this->transferTimeout << ($connectAttemptIdx - 1);

        return min($timeout, self::MAX_TRANSFER_TIMEOUT);
    }

    /**
     * @param Request $request
     * @param int|null $apiVersion
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     * @throws ConnectionTimeout
     */
    protected function sendRequest($request, $apiVersion = null): ResponseInterface
    {
        $lastConnectionTimeout = $this->connectionTimeout;
        $lastTransferTimeout = $this->transferTimeout;

        for ($connectAttemptIdx = 1; $connectAttemptIdx <= $this->connectionMaxNumAttempts; $connectAttemptIdx++) {
            try {
                return parent::sendRequest($request, $apiVersion);
            } catch (ClientExceptionInterface $e) {
                if ($e->getCode() === CURLE_OPERATION_TIMEDOUT) {
                    if ($connectAttemptIdx < $this->connectionMaxNumAttempts) {
                        $lastConnectionTimeout = $this->calculateConnectionTimeout($connectAttemptIdx + 1);
                        $lastTransferTimeout = $this->calculateTransferTimeout($connectAttemptIdx + 1);

                        $this->client = $this->createClient($lastConnectionTimeout, $lastTransferTimeout, $this->options);

                        continue;
                    }

                    throw new ConnectionTimeout(
                        $e->getMessage(),
                        $e->getCode(),
                        $e,
                        $connectAttemptIdx,
                        $lastConnectionTimeout,
                        $lastTransferTimeout,
                        $request->getRequestUri() ?? '',
                        $request->getRequestBody() ?? ''
                    );
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Unexpected state: retry loop completed without return or exception.');
    }

    /**
     * @param int $connectionTimeout
     * @param int $transferTimeout
     * @param array $options
     * @return \ComfinoExternal\Sunrise\Http\Client\Curl\Client
     */
    protected function createClient($connectionTimeout, $transferTimeout, $options = []): \ComfinoExternal\Sunrise\Http\Client\Curl\Client
    {
        $clientOptions = [
            CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
            CURLOPT_TIMEOUT => $transferTimeout,
        ];

        foreach ($options as $optionKey => $optionValue) {
            $clientOptions[$optionKey] = $optionValue;
        }

        return new \ComfinoExternal\Sunrise\Http\Client\Curl\Client(self::$responseFactory, $clientOptions);
    }
}
