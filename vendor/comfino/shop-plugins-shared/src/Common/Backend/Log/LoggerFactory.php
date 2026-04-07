<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Log;

use ComfinoExternal\Monolog\Formatter\LineFormatter;
use ComfinoExternal\Monolog\Handler\RotatingFileHandler;
use ComfinoExternal\Monolog\Handler\StreamHandler;
use ComfinoExternal\Monolog\Logger;
use ComfinoExternal\Monolog\Processor\PsrLogMessageProcessor;

final class LoggerFactory
{
    /**
     * @param string $logFilePath
     * @param string $minLevel
     * @param bool $enableSanitization
     * @return Logger
     */
    public static function createDebugLogger(string $logFilePath, string $minLevel = 'debug', bool $enableSanitization = true): Logger
    {
        self::ensureLogDirectory($logFilePath);

        $logger = new Logger('comfino_debug');

        $level = self::stringToLevel($minLevel);

        $handler = new RotatingFileHandler($logFilePath, 5, $level, true, 0644);
        $handler->setFormatter(
            new LineFormatter(
                "[%datetime%] [%level_name%] %message% %context%\n",
                "Y-m-d H:i:s",
                true,
                true
            )
        );

        $logger->pushHandler($handler);

        if ($enableSanitization) {
            $logger->pushProcessor(new SensitiveDataProcessor());
        }

        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    }

    /**
     * @param string $logFilePath
     * @param bool $enableSanitization
     * @return Logger
     * @throws \Exception
     */
    public static function createErrorLogger(string $logFilePath, bool $enableSanitization = true): Logger
    {
        self::ensureLogDirectory($logFilePath);

        $logger = new Logger('comfino_error');

        $level = self::stringToLevel('error');

        $handler = new StreamHandler($logFilePath, $level, true, 0644);
        $handler->setFormatter(
            new LineFormatter(
                "[%datetime%] [%level_name%] %message% %context%\n",
                "Y-m-d H:i:s",
                true,
                true
            )
        );

        $logger->pushHandler($handler);

        if ($enableSanitization) {
            $logger->pushProcessor(new SensitiveDataProcessor());
        }

        return $logger;
    }

    /**
     * @param string $logFilePath
     * @param int $maxFileSize
     * @param int $maxFiles
     * @param string $minLevel
     * @param bool $enableSanitization
     * @return Logger
     */
    public static function createSizeRotatingLogger(
        string $logFilePath,
        int $maxFileSize = 10485760,
        int $maxFiles = 5,
        string $minLevel = 'debug',
        bool $enableSanitization = true
    ): Logger {
        self::ensureLogDirectory($logFilePath);

        $logger = new Logger('comfino_debug');
        $level = self::stringToLevel($minLevel);

        $handler = new RotatingFileHandler($logFilePath, $maxFiles, $level, true, 0644);
        $handler->setFormatter(
            new LineFormatter(
                "[%datetime%] [%level_name%] %message% %context%\n",
                "Y-m-d H:i:s",
                true,
                true
            )
        );

        $logger->pushHandler($handler);

        if ($enableSanitization) {
            $logger->pushProcessor(new SensitiveDataProcessor());
        }

        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    }

    /**
     * @param string $level
     * @return int|object
     */
    private static function stringToLevel(string $level)
    {
        $levelUpper = strtoupper($level);

        if (class_exists('\Monolog\Level')) {
            $levelEnum = '\Monolog\Level';

            switch ($levelUpper) {
                case 'DEBUG':
                    return $levelEnum::Debug;
                case 'INFO':
                    return $levelEnum::Info;
                case 'NOTICE':
                    return $levelEnum::Notice;
                case 'WARNING':
                    return $levelEnum::Warning;
                case 'ERROR':
                    return $levelEnum::Error;
                case 'CRITICAL':
                    return $levelEnum::Critical;
                case 'ALERT':
                    return $levelEnum::Alert;
                case 'EMERGENCY':
                    return $levelEnum::Emergency;
                default:
                    return $levelEnum::Debug;
            }
        }

        switch ($levelUpper) {
            case 'DEBUG':
                return Logger::DEBUG;
            case 'INFO':
                return Logger::INFO;
            case 'NOTICE':
                return Logger::NOTICE;
            case 'WARNING':
                return Logger::WARNING;
            case 'ERROR':
                return Logger::ERROR;
            case 'CRITICAL':
                return Logger::CRITICAL;
            case 'ALERT':
                return Logger::ALERT;
            case 'EMERGENCY':
                return Logger::EMERGENCY;
            default:
                return Logger::DEBUG;
        }
    }

    /**
     * @param string $logFilePath
     */
    private static function ensureLogDirectory(string $logFilePath): void
    {
        $logDir = dirname($logFilePath);

        if (!is_dir($logDir) && !mkdir($logDir, 0755, true) && !is_dir($logDir)) {
            return;
        }

        if (!file_exists($htaccessPath = $logDir . DIRECTORY_SEPARATOR . '.htaccess')) {
            file_put_contents($htaccessPath, "Order deny,allow\nDeny from all\n");
        }

        if (!file_exists($indexPath = $logDir . DIRECTORY_SEPARATOR . 'index.php')) {
            file_put_contents($indexPath, "<?php\n// Silence is golden.\n");
        }
    }
}
