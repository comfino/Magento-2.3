<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Dto\Plugin;

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
     * @var string
     */
    public $pluginVersion;
    /**
     * @var string
     */
    public $platformVersion;
    /**
     * @var string
     */
    public $phpVersion;
    /**
     * @var ErrorCategory
     */
    public $category;
    /**
     * @var ErrorSeverity
     */
    public $severity;
    /**
     * @var OperationContext
     */
    public $context;
    /**
     * @var string
     */
    public $errorCode;
    /**
     * @var string
     */
    public $errorMessage;
    /**
     * @var array<array-key,
     */
    public $environment = [];
    /**
     * @var string|null
     */
    public $apiEndpoint;
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
     * @var int|null
     */
    public $occurredAt;
    /**
     * @param string $host
     * @param string $platform
     * @param string $pluginVersion
     * @param string $platformVersion
     * @param string $phpVersion
     * @param ErrorCategory $category
     * @param ErrorSeverity $severity
     * @param OperationContext $context
     * @param string $errorCode
     * @param string $errorMessage
     * @param string|null $apiEndpoint
     * @param string|null $apiRequestUrl
     * @param string|null $apiRequest
     * @param string|null $apiResponse
     * @param string|null $stackTrace
     * @param int|null $occurredAt
     */
    public function __construct(string $host, string $platform, string $pluginVersion, string $platformVersion, string $phpVersion, ErrorCategory $category, ErrorSeverity $severity, OperationContext $context, string $errorCode, string $errorMessage, array $environment = [], ?string $apiEndpoint = null, ?string $apiRequestUrl = null, ?string $apiRequest = null, ?string $apiResponse = null, ?string $stackTrace = null, ?int $occurredAt = null)
    {
        $this->host = $host;
        $this->platform = $platform;
        $this->pluginVersion = $pluginVersion;
        $this->platformVersion = $platformVersion;
        $this->phpVersion = $phpVersion;
        $this->category = $category;
        $this->severity = $severity;
        $this->context = $context;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->environment = $environment;
        $this->apiEndpoint = $apiEndpoint;
        $this->apiRequestUrl = $apiRequestUrl;
        $this->apiRequest = $apiRequest;
        $this->apiResponse = $apiResponse;
        $this->stackTrace = $stackTrace;
        $this->occurredAt = $occurredAt;
    }
}
