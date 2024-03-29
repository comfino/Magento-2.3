<?php

namespace Comfino\ComfinoGateway\Model\Connector\Service;

use Comfino\ComfinoGateway\Helper\Data;
use Exception;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session;
use Symfony\Component\HttpFoundation\Response;

abstract class ServiceAbstract
{
    private const POSITIVE_HTTP_CODES = [
        Response::HTTP_ACCEPTED,
        Response::HTTP_OK,
        Response::HTTP_CONTINUE,
        Response::HTTP_CREATED
    ];

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
     * Returns API URL depending on sandbox activation state.
     *
     * @return string
     */
    protected function getApiUrl(): string
    {
        return $this->helper->isSandboxEnabled() ? $this->helper->getSandboxUrl() : $this->helper->getProdUrl();
    }

    /**
     * Returns API key depending on sandbox activation state.
     *
     * @return string
     */
    protected function getApiKey(): string
    {
        return $this->helper->isSandboxEnabled() ? $this->helper->getSandboxApiKey() : $this->helper->getApiKey();
    }

    /**
     * Sends GET request.
     *
     * @param string $url
     * @param array $params
     *
     * @return bool
     */
    protected function sendGetRequest(string $url, array $params = []): bool
    {
        $requestParams = http_build_query($params);
        $requestUrl = "$url?$requestParams";

        $this->logger->info(
            'REQUEST',
            [
                'url' => $url,
                'method' => 'GET',
                'params' => $requestParams,
                'body' => ''
            ]
        );

        $this->prepareHeaders();

        try {
            $this->curl->get($requestUrl);
        } catch (Exception $e) {
            $this->logger->error(
                'Communication error',
                [
                    'errorMessage' => $e->getMessage(),
                    'httpStatus' => $this->curl->getStatus(),
                    'url' => $requestUrl,
                    'method' => 'GET',
                    'response' => $this->curl->getBody()
                ]
            );
        }

        return $this->isSuccessful();
    }

    /**
     * Sends POST request.
     *
     * @param string $url
     * @param mixed $body
     *
     * @return bool
     */
    protected function sendPostRequest(string $url, $body): bool
    {
        $this->logger->info(
            'REQUEST',
            [
                'url' => $url,
                'method' => 'POST',
                'params' => '',
                'body' => $body
            ]
        );

        $this->prepareHeaders();

        try {
            $this->curl->post($url, $body);
        } catch (Exception $e) {
            $this->logger->error(
                'Communication error',
                [
                    'errorMessage' => $e->getMessage(),
                    'httpStatus' => $this->curl->getStatus(),
                    'url' => $url,
                    'method' => 'POST',
                    'response' => $this->curl->getBody()
                ]
            );
        }

        return $this->isSuccessful();
    }

    /**
     * Sends PUT request.
     *
     * @param string $url
     * @param mixed $body
     *
     * @return bool
     */
    protected function sendPutRequest(string $url, $body = null): bool
    {
        $this->logger->info(
            'REQUEST',
            [
                'url' => $url,
                'method' => 'PUT',
                'params' => '',
                'body' => $body
            ]
        );

        $this->prepareHeaders();
        $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');

        try {
            $this->curl->post($url, $body);
        } catch (Exception $e) {
            $this->logger->error(
                'Communication error',
                [
                    'errorMessage' => $e->getMessage(),
                    'httpStatus' => $this->curl->getStatus(),
                    'url' => $url,
                    'method' => 'PUT',
                    'response' => $this->curl->getBody()
                ]
            );
        }

        return $this->isSuccessful();
    }

    /**
     * Prepares headers for CURL request.
     *
     * @return void
     */
    private function prepareHeaders(): void
    {
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Api-Key', $this->getApiKey());
        $this->curl->addHeader('User-Agent', $this->getUserAgent());
    }

    /**
     * Returns User Agent from ProductMetaData (name and version).
     *
     * @return string
     */
    private function getUserAgent(): string
    {
        return sprintf(
            'Magento Comfino [%s, %s], Magento [%s], PHP [%s]',
            $this->helper->getModuleVersion(),
            $this->helper->getSetupVersion(),
            $this->productMetaData->getVersion(),
            PHP_VERSION
        );
    }

    /**
     * Decodes JSON.
     *
     * @param $json
     *
     * @return array|bool|float|int|string
     */
    protected function decode($json)
    {
        return $this->serializer->unserialize($json) ?? [];
    }

    /**
     * Encodes array to JSON.
     *
     * @param $json
     *
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
        $hash = hash('sha3-256', $this->helper->getApiKey().$jsonData);

        return $crSignature === $hash;
    }

    /**
     * @return bool
     */
    private function isSuccessful(): bool
    {
        return in_array($this->curl->getStatus(), self::POSITIVE_HTTP_CODES, true);
    }
}
