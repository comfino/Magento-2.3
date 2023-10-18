<?php

namespace Comfino\ComfinoGateway\Logger;

use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Model\Connector\Service\ServiceAbstract;
use Comfino\ComfinoGateway\Model\ErrorLogger\ShopPluginError;
use Psr\Log\LoggerInterface;

class ErrorLogger
{
    /**
     * @var ServiceAbstract
     */
    private static $service;

    /**
     * @var Data
     */
    private static $helper;

    /**
     * @var LoggerInterface
     */
    private static $logger;

    public static function sendError(
        string $errorPrefix,
        string $errorCode,
        string $errorMessage,
        ?string $apiRequestUrl = null,
        ?string $apiRequest = null,
        ?string $apiResponse = null,
        ?string $stackTrace = null
    ): void
    {
        if (self::$service === null) {
            return;
        }

        $error = new ShopPluginError(
            self::$helper->getShopDomain(),
            'Magento',
            [
                'plugin_version' => self::$helper->getModuleVersion(),
                'shop_version' => self::$helper->getShopVersion(),
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'],
                'server_name' => $_SERVER['SERVER_NAME'],
                'server_addr' => $_SERVER['SERVER_ADDR'],
                'database_version' => self::$helper->getDatabaseVersion(),
            ],
            $errorCode,
            "$errorPrefix: $errorMessage",
            $apiRequestUrl,
            $apiRequest,
            $apiResponse,
            $stackTrace
        );

        if (getenv('COMFINO_DEBUG') === 'TRUE') {
            // Disable custom errors handling if plugin is in debug mode.
            $disableErrorsSending = true;
        } else {
            $disableErrorsSending = false;
        }

        if ($disableErrorsSending || !self::$service->sendLoggedError($error)) {
            $request_info = [];

            if ($apiRequestUrl !== null) {
                $request_info[] = "API URL: $apiRequestUrl";
            }

            if ($apiRequest !== null) {
                $request_info[] = "API request: $apiRequest";
            }

            if ($apiResponse !== null) {
                $request_info[] = "API response: $apiResponse";
            }

            if (count($request_info)) {
                $errorMessage .= "\n" . implode("\n", $request_info);
            }

            if ($stackTrace !== null) {
                $errorMessage .= "\nStack trace: $stackTrace";
            }

            self::$logger->error($errorPrefix . ': ' . $errorMessage);
        }
    }

    public static function init(ServiceAbstract $service, Data $helper, LoggerInterface $logger): void
    {
        static $initialized = false;

        if (!$initialized) {
            self::$service = $service;
            self::$helper = $helper;
            self::$logger = $logger;

            $initialized = true;
        }
    }
}
