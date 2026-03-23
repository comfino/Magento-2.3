<?php

namespace Comfino\Api;

use Comfino\Common\Backend\Factory\ApiClientFactory;
use Comfino\Common\Exception\ConnectionTimeout;
use Comfino\Configuration\ConfigManager;
use Comfino\ComfinoGateway\Helper\Data;
use Comfino\DebugLogger;
use Comfino\ErrorLogger;
use ComfinoExternal\Psr\Http\Client\NetworkExceptionInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Magento-specific API client factory.
 */
final class ApiClient
{
    private static ?\Comfino\Common\Api\Client $apiClient = null;

    public static function getInstance(?bool $sandboxMode = null, ?string $apiKey = null): \Comfino\Common\Api\Client
    {
        if ($sandboxMode === null) {
            $sandboxMode = ConfigManager::isSandboxMode();
        }

        if ($apiKey === null) {
            $apiKey = ConfigManager::getApiKey();
        }

        /** @var Data $helper */
        $helper = ObjectManager::getInstance()->get(Data::class);

        if (self::$apiClient === null) {
            self::$apiClient = (new ApiClientFactory())->createClient(
                $apiKey,
                sprintf(
                    'MG Comfino [%s], MG [%s], PHP [%s], %s',
                    $helper->getModuleVersion(),
                    $helper->getShopVersion(),
                    PHP_VERSION,
                    $helper->getShopDomain()
                ),
                ConfigManager::getApiHost(),
                $helper->getShopLanguage(),
                (int) ConfigManager::getConfigurationValue('COMFINO_API_CONNECT_TIMEOUT', 3),
                (int) ConfigManager::getConfigurationValue('COMFINO_API_TIMEOUT', 5),
                (int) ConfigManager::getConfigurationValue('COMFINO_API_CONNECT_NUM_ATTEMPTS', 3)
            );
        } else {
            self::$apiClient->setCustomApiHost(ConfigManager::getApiHost());
            self::$apiClient->setApiKey($apiKey);
            self::$apiClient->setApiLanguage($helper->getShopLanguage());
        }

        if ($sandboxMode) {
            self::$apiClient->enableSandboxMode();
        } else {
            self::$apiClient->disableSandboxMode();
        }

        return self::$apiClient;
    }

    /**
     * Processes an API exception: logs rich context to DebugLogger and sends the error via ErrorLogger.
     *
     * Handles HttpErrorExceptionInterface (including ConnectionTimeout) and NetworkExceptionInterface
     * specially to extract URL, request/response bodies and timeout details.
     * For all other exceptions a generic [API_ERROR] debug event is emitted.
     *
     * @param string $errorPrefix Context label shown in log entries (e.g. "Cancel order error").
     * @param \Throwable $exception
     */
    public static function processApiError(string $errorPrefix, \Throwable $exception): void
    {
        $url = null;
        $requestBody = null;
        $responseBody = null;

        if ($exception instanceof HttpErrorExceptionInterface) {
            $url = $exception->getUrl();
            $requestBody = $exception->getRequestBody();
            $responseBody = $exception->getResponseBody();

            if ($exception instanceof ConnectionTimeout) {
                DebugLogger::logEvent(
                    '[API_TIMEOUT]',
                    $errorPrefix,
                    [
                        'exception' => $exception->getPrevious() !== null ? get_class($exception->getPrevious()) : '',
                        'code' => $exception->getPrevious() !== null ? $exception->getPrevious()->getCode() : 0,
                        'connect_attempt_idx' => $exception->getConnectAttemptIdx(),
                        'connection_timeout' => $exception->getConnectionTimeout(),
                        'transfer_timeout' => $exception->getTransferTimeout(),
                    ]
                );
            }
        } elseif ($exception instanceof NetworkExceptionInterface) {
            $exception->getRequest()->getBody()->rewind();

            DebugLogger::logEvent('[API_NETWORK_ERROR]', $errorPrefix . " [{$exception->getMessage()}]");

            $url = $exception->getRequest()->getRequestTarget();
            $requestBody = $exception->getRequest()->getBody()->getContents();
        }

        DebugLogger::logEvent(
            '[API_ERROR]',
            $errorPrefix,
            [
                'exception'     => get_class($exception),
                'error_message' => $exception->getMessage(),
                'error_code'    => $exception->getCode(),
                'error_file'    => $exception->getFile(),
                'error_line'    => $exception->getLine(),
                'error_trace'   => $exception->getTraceAsString(),
            ]
        );

        ErrorLogger::sendError(
            $exception,
            $errorPrefix,
            (string) $exception->getCode(),
            $exception->getMessage(),
            $url !== null && $url !== '' ? $url : null,
            $requestBody !== null && $requestBody !== '' ? $requestBody : null,
            $responseBody !== null && $responseBody !== '' ? $responseBody : null,
            $exception->getTraceAsString()
        );
    }
}
