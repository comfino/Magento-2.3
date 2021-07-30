<?php

namespace Comperia\ComperiaGateway\Model\Connector\Service;

use Comperia\ComperiaGateway\Helper\Data;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session;

abstract class ServiceAbstract
{
    /**
     * @var Curl
     */
    protected $curl;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var SerializerInterface
     */
    protected $serializer;
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Session
     */
    protected $session;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetaData;
    /**
     * @var Request
     */
    protected $request;

    /**
     * ApiConnector constructor.
     *
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param Data $helper
     * @param Session $session
     * @param ProductMetadataInterface $productMetadata
     * @param Request $request
     */
    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        Data $helper,
        Session $session,
        ProductMetadataInterface $productMetadata,
        Request $request
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->helper = $helper;
        $this->session = $session;
        $this->productMetaData = $productMetadata;
        $this->request = $request;
    }

    /**
     * Get Api Url depending on sandbox activation state
     * @return string
     */
    protected function getApiUrl(): string
    {
        return $this->helper->isSandboxEnabled() ? $this->helper->getSandboxUrl() : $this->helper->getProdUrl();
    }
    /**
     * Send Post Request
     * @param $url
     * @param $body
     * @return void
     */
    protected function sendPostRequest($url, $body): void
    {
        $this->logger->info('REQUEST', ['url' => $url, 'transaction' => $body]);
        $this->prepareHeaders();
        $this->curl->post($url, $body);
        $this->logger->info('RESPONSE', ['response' => $this->curl->getBody()]);
    }

    /**
     * Send Get Request
     * @param $url
     * @param $params
     * @return void
     */
    protected function sendGetRequest($url, $params): void
    {
        $this->logger->info(
            'REQUEST',
            [
                'url' => $url,
                'method' => 'GET',
                'params' => http_build_query($params),
                'body' => ''
            ]
        );
        $this->prepareHeaders();
        $this->curl->get($url . '?' . http_build_query($params));
        $this->logger->info('RESPONSE', ['response' => $this->curl->getBody()]);
    }

    /**
     * Prepare Headers for CURL request
     * @return void
     */
    private function prepareHeaders(): void
    {
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Api-Key', $this->helper->getApiKey());
        $this->curl->addHeader('User-Agent', $this->getUserAgent());
    }

    /**
     * Get User Agent  from ProductMetaData (name and version)
     * @return string
     */
    private function getUserAgent(): string
    {
        return $this->productMetaData->getName() . ' ' . $this->productMetaData->getVersion();
    }

    /**
     * Decode json
     * @param $json
     * @return array|bool|float|int|string
     */
    protected function decode($json)
    {
        return $this->serializer->unserialize($json) ?? [];
    }

    /**
     * Encode array to json
     * @param $json
     * @return bool|string
     */
    protected function encode($json)
    {
        return $this->serializer->serialize($json);
    }

    /**
     * @param string $jsonData
     *
     * @return bool
     */
    protected function isValidSignature(string $jsonData): bool
    {
        $crSignature = $this->request->getHeader('CR-Signature');
        $hash = hash('sha3-256', $this->helper->getApiKey() . $jsonData);

        return $crSignature === $hash;
    }
}
