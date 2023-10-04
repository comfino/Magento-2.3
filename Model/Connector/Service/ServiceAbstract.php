<?php

namespace Comfino\ComfinoGateway\Model\Connector\Service;

use Comfino\ComfinoGateway\Helper\Data;
use Exception;
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
     * @var Request
     */
    protected $request;

    public function __construct(Curl $curl, LoggerInterface $logger, SerializerInterface $serializer, Data $helper, Session $session, Request $request)
    {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->helper = $helper;
        $this->session = $session;
        $this->request = $request;
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
     * @return bool|string
     */
    protected function encode($json)
    {
        return $this->serializer->serialize($json);
    }

    /**
     * Prepares headers for CURL request.
     */
    private function prepareHeaders(): void
    {
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Api-Key', $this->helper->getApiKey());
        $this->curl->addHeader('Api-Language', $this->helper->getShopLanguage());
        $this->curl->addHeader('User-Agent', $this->getUserAgent());
    }

    /**
     * Returns User Agent from ProductMetaData (name and version).
     */
    private function getUserAgent(): string
    {
        return sprintf(
            'MG Comfino [%s], MG [%s], PHP [%s], %s',
            $this->helper->getModuleVersion(),
            $this->helper->getShopVersion(),
            PHP_VERSION,
            $this->helper->getShopDomain()
        );
    }

    private function isSuccessful(): bool
    {
        return in_array($this->curl->getStatus(), self::POSITIVE_HTTP_CODES, true);
    }
}
