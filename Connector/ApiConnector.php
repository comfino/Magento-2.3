<?php

namespace Comperia\ComperiaGateway\Connector;

use Comperia\ComperiaGateway\Connector\Transaction\ApplicationTransaction;
use Comperia\ComperiaGateway\Connector\Transaction\Response\ApplicationResponse;
use Comperia\ComperiaGateway\Connector\Transaction\Response\TransactionResponse;
use Comperia\ComperiaGateway\Connector\Transaction\Transaction;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

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
    const URL_PROD = 'payment/comperiapayment/produrl';
    const URL_DEV = 'payment/comperiapayment/sandboxurl';
    const APPLICATION_URI = '/v1/orders';
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ApiConnector constructor.
     *
     * @param ScopeConfigInterface   $scopeConfig
     * @param Curl                   $curl
     * @param ApplicationTransaction $applicationTransaction
     * @param LoggerInterface        $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        ApplicationTransaction $applicationTransaction,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->applicationTransaction = $applicationTransaction;
        $this->logger = $logger;
    }

    /**
     * @param Transaction $transaction
     * @param string      $responseClass
     *
     * @return TransactionResponse
     */
    private function send(
        Transaction $transaction,
        string $responseClass = TransactionResponse::class
    ): TransactionResponse {
        $apiUrl = ($sandbox === "0" ? $urlProd : $urlDev) . '/' . $transaction::PATH;
        $urlDev  = $this->scopeConfig->getValue(self::URL_DEV, ScopeInterface::SCOPE_STORE);
        $urlProd = $this->scopeConfig->getValue(self::URL_PROD, ScopeInterface::SCOPE_STORE);
        $sandbox = $this->scopeConfig->getValue(self::SANDBOX, ScopeInterface::SCOPE_STORE);
        $this->logger->info('Request to open an application', ['url' => $apiUrl, 'transaction' => $transaction->getBody()]);
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Api-Key', $this->scopeConfig->getValue(self::API_KEY, ScopeInterface::SCOPE_STORE));
        $this->curl->post($apiUrl, $transaction->getBody());
        $this->logger->info('Response to the request to open an application', ['response' => $this->curl->getBody()]);

        return new $responseClass($this->curl->getStatus(), json_decode($this->curl->getBody(), true));
    }

    /**
     * @return TransactionResponse
     */
    public function applicationTransaction(): TransactionResponse
    {
        return $this->send($this->applicationTransaction, ApplicationResponse::class);
    }
}
