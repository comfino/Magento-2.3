<?php

declare(strict_types=1);

namespace Comfino\Common\Backend;

use Comfino\Common\Backend\Log\LoggerFactory;
use ComfinoExternal\Monolog\Logger as MonologLogger;

class DebugLogger extends Logger
{
    /**
     * @var string
     */
    private $logFilePath;
    /**
     * @var $this|null
     */
    private static $instance;
    /**
     * @var MonologLogger|null
     */
    private static $logger;

    /**
     * @param string $logFilePath
     * @return self
     */
    public static function getInstance($logFilePath): self
    {
        if (self::$instance === null) {
            self::$instance = new self($logFilePath);
        }

        return self::$instance;
    }

    private function __construct(string $logFilePath)
    {
        $this->logFilePath = $logFilePath;
    }

    /**
     * @return MonologLogger
     */
    public function getLogger(): MonologLogger
    {
        if (self::$logger === null) {
            self::$logger = LoggerFactory::createDebugLogger($this->logFilePath);
        }

        return self::$logger;
    }

    /**
     * @param string $eventPrefix
     * @param string $eventMessage
     * @param array|null $parameters
     */
    public function logEvent($eventPrefix, $eventMessage, $parameters = null): void
    {
        $this->getLogger()->debug($eventPrefix . ': ' . $eventMessage, $parameters ?? []);
    }

    /**
     * @param int $numLines
     * @return string
     */
    public function getDebugLog($numLines): string
    {
        if (empty($this->logFilePath)) {
            return '';
        }

        $actualLogPath = $this->findActualLogFile($this->logFilePath);

        if ($actualLogPath === null || !file_exists($actualLogPath)) {
            return '';
        }

        return implode('', FileUtils::readLastLines($actualLogPath, $numLines));
    }

    /**
     * @return int
     */
    public function clearLogs(): int
    {
        if ($this->logFilePath === null) {
            return 0;
        }

        return $this->clearLogFiles($this->logFilePath);
    }
}
