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
     * @var mixed[]
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

    public function __construct(
        string $host,
        string $platform,
        array $environment,
        string $errorCode,
        string $errorMessage,
        ?string $apiRequestUrl = null,
        ?string $apiRequest = null,
        ?string $apiResponse = null,
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
