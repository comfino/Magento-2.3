<?php

namespace Comfino;

use Comfino\Api\ApiClient;
use Comfino\Api\Exception\AuthorizationError;
use Comfino\Api\Exception\ResponseValidationError;
use Comfino\ComfinoGateway\Helper\Data;
use Comfino\Configuration\ConfigManager;
use Magento\Framework\App\ObjectManager;

/**
 * Error logging facade for Magento.
 *
 * @see Common\Backend\ErrorLogger
 */
final class ErrorLogger
{
    private static ?Common\Backend\ErrorLogger $errorLogger = null;

    public static function init(): void
    {
        static $initialized = false;

        if (!$initialized) {
            self::getLoggerInstance()->init();

            $initialized = true;
        }
    }

    public static function getLoggerInstance(): Common\Backend\ErrorLogger
    {
        if (self::$errorLogger === null) {
            self::$errorLogger = Common\Backend\ErrorLogger::getInstance(
                ApiClient::getInstance(),
                BP . '/var/log/comfino_errors.log',
                self::getShopDomain(),
                'Magento',
                'Comfino_ComfinoGateway',
                ConfigManager::getEnvironmentInfo()
            );
        }

        return self::$errorLogger;
    }

    public static function sendError(
        \Throwable $exception,
        string $errorPrefix,
        string $errorCode,
        string $errorMessage,
        string $apiRequestUrl = null,
        string $apiRequest = null,
        string $apiResponse = null,
        string $stackTrace = null
    ): void {
        if ($exception instanceof ResponseValidationError || $exception instanceof AuthorizationError) {
            /* - Don't collect validation errors - validation errors are already collected at API side (response with status code 400).
               - Don't collect authorization errors caused by empty or wrong API key (response with status code 401). */
            return;
        }

        self::getLoggerInstance()->sendError(
            $errorPrefix, $errorCode, $errorMessage, $apiRequestUrl, $apiRequest, $apiResponse, $stackTrace
        );
    }

    public static function getErrorLog(int $numLines): string
    {
        return self::getLoggerInstance()->getErrorLog($numLines);
    }

    public static function clearLogs(): void
    {
        self::getLoggerInstance()->clearLogs();
    }

    private static function getShopDomain(): string
    {
        /** @var Data $helper */
        $helper = ObjectManager::getInstance()->get(Data::class);

        return $helper->getShopDomain();
    }
}
