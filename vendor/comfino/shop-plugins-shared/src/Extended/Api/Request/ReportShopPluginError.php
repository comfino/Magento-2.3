<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Request;

use Comfino\Api\Exception\RequestValidationError;
use Comfino\Api\Request;
use Comfino\Extended\Api\Dto\Plugin\ShopPluginError;

class ReportShopPluginError extends Request
{
    /**
     * @var \Comfino\Extended\Api\Dto\Plugin\ShopPluginError
     */
    private $shopPluginError;
    /**
     * @var string
     */
    private $hashKey;
    public function __construct(ShopPluginError $shopPluginError, string $hashKey)
    {
        $this->shopPluginError = $shopPluginError;
        $this->hashKey = $hashKey;
        $this->setRequestMethod('POST');
        $this->setApiEndpointPath('log-plugin-error');
    }

    protected function prepareRequestBody(): ?array
    {
        $errorDetailsArray = [
            'host' => $this->shopPluginError->host,
            'platform' => $this->shopPluginError->platform,
            'environment' => $this->shopPluginError->environment,
            'error_code' => $this->shopPluginError->errorCode,
            'error_message' => $this->shopPluginError->errorMessage,
            'api_request_url' => $this->shopPluginError->apiRequestUrl,
            'api_request' => $this->shopPluginError->apiRequest,
            'api_response' => $this->shopPluginError->apiResponse,
            'stack_trace' => $this->shopPluginError->stackTrace,
        ];

        if (($errorDetails = gzcompress($this->serializer->serialize($errorDetailsArray), 9)) === false) {
            throw new RequestValidationError('Error report preparation failed.');
        }

        $encodedErrorDetails = base64_encode($errorDetails);

        return [
            'error_details' => $encodedErrorDetails,
            'hash' => hash_hmac('sha3-256', $encodedErrorDetails, $this->hashKey),
        ];
    }
}
