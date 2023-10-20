<?php

namespace Comfino\ComfinoGateway\Model\Connector\Service;

use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Logger\ErrorLogger;
use Comfino\ComfinoGateway\Model\ErrorLogger\ShopPluginError;
use Comfino\ComfinoGateway\Model\ErrorLogger\ShopPluginErrorRequest;
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
        Response::HTTP_CREATED,
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

    /**
     * @var string
     */
    protected $lastUrl;

    /**
     * @var string[]
     */
    protected $lastErrors = [];

    public function __construct(Curl $curl, LoggerInterface $logger, SerializerInterface $serializer, Data $helper, Session $session, Request $request)
    {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->helper = $helper;
        $this->session = $session;
        $this->request = $request;

        ErrorLogger::init($this, $helper, $logger);
    }

    public function getLastResponseCode(): int
    {
        return $this->curl->getStatus();
    }

    public function getLastUrl(): ?string
    {
        return $this->lastUrl;
    }

    public function getLastErrors(): array
    {
        return $this->lastErrors;
    }

    public function sendLoggedError(ShopPluginError $error): bool
    {
        $request = new ShopPluginErrorRequest();

        if (!$request->prepareRequest($error, $this->getUserAgentHeader())) {
            $this->logger->error('Error request preparation failed: ' . $error->errorMessage);

            return false;
        }

        $data = ['error_details' => $request->errorDetails, 'hash' => $request->hash];
        $response = $this->sendPostRequest($this->helper->getApiHost() . '/v1/log-plugin-error', $this->serializer->serialize($data), false);

        return strpos($response, 'errors') === false;
    }

    protected function sendGetRequest(string $url, array $params = []): bool
    {
        $requestUrl = $url;

        if (count($params)) {
            $requestParams = http_build_query($params);
            $requestUrl .= "?$requestParams";
        }

        $this->lastUrl = $requestUrl;

        $this->prepareHeaders();

        try {
            $this->curl->get($requestUrl);
        } catch (Exception $e) {
            $this->lastErrors[] = $e->getMessage();

            ErrorLogger::sendError(
                'Communication error',
                $this->curl->getStatus(),
                $e->getMessage(),
                $requestUrl,
                null,
                $this->curl->getBody(),
                $e->getTraceAsString()
            );

            return false;
        }

        return $this->isSuccessful();
    }

    protected function sendPostRequest(string $url, string $body, bool $logErrors = true): bool
    {
        $this->prepareHeaders();

        $this->lastUrl = $url;

        try {
            $this->curl->post($url, $body);
        } catch (Exception $e) {
            $this->lastErrors[] = $e->getMessage();

            if ($logErrors) {
                ErrorLogger::sendError(
                    'Communication error',
                    $this->curl->getStatus(),
                    $e->getMessage(),
                    $url,
                    $body,
                    $this->curl->getBody(),
                    $e->getTraceAsString()
                );
            }

            return false;
        }

        return $this->isSuccessful();
    }

    protected function sendPutRequest(string $url, $body = null): bool
    {
        $this->prepareHeaders();
        $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');

        $this->lastUrl = $url;

        try {
            $this->curl->post($url, $body);
        } catch (Exception $e) {
            $this->lastErrors[] = $e->getMessage();

            ErrorLogger::sendError(
                'Communication error',
                $this->curl->getStatus(),
                $e->getMessage(),
                $url,
                $body,
                $this->curl->getBody(),
                $e->getTraceAsString()
            );

            return false;
        }

        return $this->isSuccessful();
    }

    protected function decode($json)
    {
        return $this->serializer->unserialize($json) ?? [];
    }

    protected function encode($json)
    {
        return $this->serializer->serialize($json);
    }

    /**
     * Returns User-Agent header from ProductMetaData (name and version).
     */
    protected function getUserAgentHeader(): string
    {
        return sprintf(
            'MG Comfino [%s], MG [%s], PHP [%s], %s',
            $this->helper->getModuleVersion(),
            $this->helper->getShopVersion(),
            PHP_VERSION,
            $this->helper->getShopDomain()
        );
    }

    /**
     * Prepares headers for cURL request.
     */
    private function prepareHeaders(): void
    {
        $this->lastErrors = [];

        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Api-Key', $this->helper->getApiKey());
        $this->curl->addHeader('Api-Language', $this->helper->getShopLanguage());
        $this->curl->addHeader('User-Agent', $this->getUserAgentHeader());
    }

    private function isSuccessful(): bool
    {
        return in_array($this->curl->getStatus(), self::POSITIVE_HTTP_CODES, true);
    }
}
