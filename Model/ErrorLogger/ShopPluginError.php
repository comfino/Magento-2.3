<?php

namespace Comfino\ComfinoGateway\Model\ErrorLogger;

final class ShopPluginError
{
    /**
     * @var string
     */
    public $host;

    /**
     * @var string
     */
    public $platform;

    /**
     * @var array
     */
    public $environment;

    /**
     * @var string
     */
    public $errorCode;

    /**
     * @var string
     */
    public $errorMessage;

    /**
     * @var string|null
     */
    public $apiRequestUrl;

    /**
     * @var string|null
     */
    public $apiRequest;

    /**
     * @var string|null
     */
    public $apiResponse;

    /**
     * @var string|null
     */
    public $stackTrace;

    /**
     * @param string $host
     * @param string $platform
     * @param array $environment
     * @param string $errorCode
     * @param string $errorMessage
     * @param string|null $apiRequestUrl
     * @param string|null $apiRequest
     * @param string|null $apiResponse
     * @param string|null $stackTrace
     */
    public function __construct(
        string  $host,
        string  $platform,
        array   $environment,
        string  $errorCode,
        string  $errorMessage,
        ?string $apiRequestUrl = null,
        ?string $apiRequest = null,
        ?string  $apiResponse = null,
        ?string $stackTrace = null
    ) {
        $this->host = $host;
        $this->platform = $platform;
        $this->environment = $environment;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->apiRequestUrl = $apiRequestUrl;
        $this->apiRequest = $apiRequest;
        $this->apiResponse = $apiResponse;
        $this->stackTrace = $stackTrace;
    }
}
