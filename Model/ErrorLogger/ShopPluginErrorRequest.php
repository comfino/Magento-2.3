<?php

namespace Comfino\ComfinoGateway\Model\ErrorLogger;

final class ShopPluginErrorRequest
{
    /**
     * @var string
     */
    public $errorDetails;

    /**
     * @var string
     */
    public $hash;

    public function prepareRequest(ShopPluginError $shopPluginError, string $hashKey): bool
    {
        $errorDetailsArray = [
            'host' => $shopPluginError->host,
            'platform' => $shopPluginError->platform,
            'environment' => $shopPluginError->environment,
            'error_code' => $shopPluginError->errorCode,
            'error_message' => $shopPluginError->errorMessage,
            'api_request_url' => $shopPluginError->apiRequestUrl,
            'api_request' => $shopPluginError->apiRequest,
            'api_response' => $shopPluginError->apiResponse,
            'stack_trace' => $shopPluginError->stackTrace,
        ];

        if (($encodedErrorDetails = json_encode($errorDetailsArray)) === false) {
            return false;
        }

        if (($error_details = gzcompress($encodedErrorDetails, 9)) === false) {
            return false;
        }

        $this->errorDetails = base64_encode($error_details);
        $this->hash = hash_hmac('sha256', $this->errorDetails, $hashKey);

        return true;
    }
}
