<?php

namespace Comfino;

use Comfino\Configuration\ConfigManager;

/**
 * Debug logging facade for Magento.
 *
 * @see Common\Backend\DebugLogger
 */
final class DebugLogger
{
    private static ?Common\Backend\DebugLogger $debugLogger;

    public static function getLoggerInstance(): Common\Backend\DebugLogger
    {
        if (self::$debugLogger === null) {
            self::$debugLogger = Common\Backend\DebugLogger::getInstance(BP . '/var/log/comfino_debug.log');
        }

        return self::$debugLogger;
    }

    public static function logEvent(string $eventPrefix, string $eventMessage, ?array $parameters = null): void
    {
        if ((!isset($_COOKIE['COMFINO_SERVICE_SESSION']) || $_COOKIE['COMFINO_SERVICE_SESSION'] !== 'ACTIVE') && ConfigManager::isServiceMode()) {
            return;
        }

        if (ConfigManager::isDebugMode()) {
            self::getLoggerInstance()->logEvent($eventPrefix, $eventMessage, $parameters);
        }
    }

    public static function clearLogs(): void
    {
        self::getLoggerInstance()->clearLogs();
    }
}
