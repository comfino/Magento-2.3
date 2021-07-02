<?php

namespace Comperia\ComperiaGateway\Connector;

use Comperia\ComperiaGateway\Connector\Offer\OffersRequest;
use Comperia\ComperiaGateway\Connector\Offer\OffersResponse;
use Comperia\ComperiaGateway\Connector\Transaction\ApplicationTransaction;
use Comperia\ComperiaGateway\Connector\Transaction\Response\ApplicationResponse;
use Comperia\ComperiaGateway\Connector\Transaction\Response\TransactionResponse;
use Comperia\ComperiaGateway\Connector\Transaction\TransactionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ApiConnector
 *
 * @package Comperia\ComperiaGateway\Connector
 */
final class ApiConnector
{
    const SANDBOX = 'payment/comperiapayment/sandbox';
    const RETURN_URL = 'payment/comperiapayment/continueurl';
    const API_KEY = 'payment/comperiapayment/apikey';
    const LOAN_TERM = 'payment/comperiapayment/loanterm';
    const URL_PROD = 'payment/comperiapayment/produrl';
    const URL_DEV = 'payment/comperiapayment/sandboxurl';
    const APPLICATION_URI = '/v1/orders';

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var ApplicationTransaction
     */
    private $applicationTransaction;

    /**
     * @var OffersRequest
     */
    private $offersRequest;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ApiConnector constructor.
     *
     * @param ScopeConfigInterface   $scopeConfig
     * @param Curl                   $curl
     * @param ApplicationTransaction $applicationTransaction
     * @param OffersRequest          $offersRequest
     * @param LoggerInterface        $logger
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Curl $curl, ApplicationTransaction $applicationTransaction, OffersRequest $offersRequest, LoggerInterface $logger)
    {
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->applicationTransaction = $applicationTransaction;
        $this->offersRequest = $offersRequest;
        $this->logger = $logger;
    }

    /**
     * @param TransactionInterface $transaction
     * @param string               $responseClass
     *
     * @return TransactionResponse
     */
    private function send(TransactionInterface $transaction, string $responseClass = TransactionResponse::class): TransactionResponse
    {
        $urlDev  = $this->scopeConfig->getValue(self::URL_DEV, ScopeInterface::SCOPE_STORE);
        $urlProd = $this->scopeConfig->getValue(self::URL_PROD, ScopeInterface::SCOPE_STORE);
        $sandbox = $this->scopeConfig->getValue(self::SANDBOX, ScopeInterface::SCOPE_STORE);
        $apiUrl = ($sandbox === "0" ? $urlProd : $urlDev) . '/' . $transaction::PATH;

        $this->logger->info('REQUEST', ['url' => $apiUrl, 'transaction' => $transaction->getBody()]);

        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Api-Key', $this->scopeConfig->getValue(self::API_KEY, ScopeInterface::SCOPE_STORE));
        $this->curl->addHeader('User-Agent', 'Magento 2.3');

        $this->curl->post($apiUrl, $transaction->getBody());

        $this->logger->info('RESPONSE', ['response' => $this->curl->getBody()]);

        return new $responseClass($this->curl->getStatus(), json_decode($this->curl->getBody(), true));
    }

    /**
     * @param RequestInterface $request
     * @param string           $method
     * @param string           $responseClass
     * @param string           $endpoint
     *
     * @return ResponseInterface
     */
    private function request(RequestInterface $request, string $method, string $responseClass, string $endpoint): ResponseInterface
    {
        $urlDev = $this->scopeConfig->getValue(self::URL_DEV, ScopeInterface::SCOPE_STORE);
        $urlProd = $this->scopeConfig->getValue(self::URL_PROD, ScopeInterface::SCOPE_STORE);
        $sandbox = $this->scopeConfig->getValue(self::SANDBOX, ScopeInterface::SCOPE_STORE);

        $apiUrl = ($sandbox === "0" ? $urlProd : $urlDev) . '/' . $endpoint;

        $this->logger->info('REQUEST', ['url' => $apiUrl, 'method' => $method, 'params' => http_build_query($request->getParams()), 'body' => $request->getBody()]);

        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Api-Key', $this->scopeConfig->getValue(self::API_KEY, ScopeInterface::SCOPE_STORE));

        if ($method === self::METHOD_GET) {
            $this->curl->get($apiUrl . '?' . http_build_query($request->getParams()));
        } else {
            $this->curl->post($apiUrl, $request->getBody());
        }

        $this->logger->info('RESPONSE', ['response' => $this->curl->getBody()]);

        return new $responseClass($this->curl->getStatus(), $request, json_decode($this->curl->getBody(), true));
    }

    /**
     * @return TransactionResponse
     */
    public function applicationTransaction(): TransactionResponse
    {
        return $this->send($this->applicationTransaction, ApplicationResponse::class);
    }

    /**
     * @return OffersResponse
     */
    public function getOffers(): OffersResponse
    {
        return $this->request($this->offersRequest, self::METHOD_GET, OffersResponse::class, OffersRequest::ENDPOINT);
    }
}
