<?php

declare(strict_types=1);

namespace Comfino\Common\Backend;

use Comfino\Common\Backend\Log\ErrorMessageNormalizer;
use Comfino\Common\Backend\Log\LoggerFactory;
use Comfino\Extended\Api\Client;
use Comfino\Extended\Api\Dto\Plugin\ErrorCategory;
use Comfino\Extended\Api\Dto\Plugin\ErrorSeverity;
use Comfino\Extended\Api\Dto\Plugin\OperationContext;
use Comfino\Extended\Api\Dto\Plugin\ShopPluginError;
use Comfino\Extended\Api\Request\ReportShopPluginError;
use ComfinoExternal\Monolog\Logger as MonologLogger;

final class ErrorLogger extends Logger
{
    /**
     * @var Client
     */
    private $apiClient;
    /**
     * @var string
     */
    private $logFilePath;
    /**
     * @var string
     */
    private $host;
    /**
     * @var string
     */
    private $platform;
    /**
     * @var string
     */
    private $modulePath;
    /**
     * @var array
     */
    private $environment;
    /**
     * @var string
     */
    private $pluginVersion;
    /**
     * @var string
     */
    private $platformVersion;
    /**
     * @var string
     */
    private $phpVersion;
    /**
     * @var ErrorMessageNormalizer
     */
    private $normalizer;
    private const CATCHED_ERRORS_MASK = E_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
    private const ERROR_TYPES = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    private const VERSION_ENV_KEYS = ['plugin_version', 'platform_version', 'shop_version', 'php_version'];

    /**
     * @var $this|null
     */
    private static $instance;
    /**
     * @var MonologLogger|null
     */
    private static $logger;

    /**
     * @param Client $apiClient
     * @param string $logFilePath
     * @param string $host
     * @param string $platform
     * @param string $modulePath
     * @param array $environment
     * @return self
     */
    public static function getInstance($apiClient, $logFilePath, $host, $platform, $modulePath, $environment): self
    {
        if (self::$instance === null) {
            $pluginVersion = (string) ($environment['plugin_version'] ?? '');
            $platformVersion = (string) ($environment['platform_version'] ?? $environment['shop_version'] ?? '');
            $phpVersion = (string) ($environment['php_version'] ?? '');

            $supplementaryEnv = array_diff_key($environment, array_flip(self::VERSION_ENV_KEYS));

            self::$instance = new self(
                $apiClient,
                $logFilePath,
                $host,
                $platform,
                $modulePath,
                $supplementaryEnv,
                $pluginVersion,
                $platformVersion,
                $phpVersion,
                new ErrorMessageNormalizer()
            );
        }

        return self::$instance;
    }

    /**
     * @param Client $apiClient
     * @param string $logFilePath
     * @param string $host
     * @param string $platform
     * @param string $modulePath
     * @param array $environment
     * @param string $pluginVersion
     * @param string $platformVersion
     * @param string $phpVersion
     * @param ErrorMessageNormalizer $normalizer
     */
    private function __construct(Client $apiClient, string $logFilePath, string $host, string $platform, string $modulePath, array $environment, string $pluginVersion, string $platformVersion, string $phpVersion, ErrorMessageNormalizer $normalizer)
    {
        $this->apiClient = $apiClient;
        $this->logFilePath = $logFilePath;
        $this->host = $host;
        $this->platform = $platform;
        $this->modulePath = $modulePath;
        $this->environment = $environment;
        $this->pluginVersion = $pluginVersion;
        $this->platformVersion = $platformVersion;
        $this->phpVersion = $phpVersion;
        $this->normalizer = $normalizer;
    }

    /**
     * @return MonologLogger
     * @throws \Exception
     */
    public function getLogger(): MonologLogger
    {
        if (self::$logger === null) {
            self::$logger = LoggerFactory::createErrorLogger($this->logFilePath);
        }

        return self::$logger;
    }

    /**
     * @param ErrorCategory $category
     * @param ErrorSeverity $severity
     * @param OperationContext $context
     * @param string $errorCode
     * @param string $errorMessage
     * @param string|null $apiRequestUrl
     * @param string|null $apiRequest
     * @param string|null $apiResponse
     * @param string|null $stackTrace
     */
    public function sendError(
        $category,
        $severity,
        $context,
        $errorCode,
        $errorMessage,
        $apiRequestUrl = null,
        $apiRequest = null,
        $apiResponse = null,
        $stackTrace = null
    ): void {
        if (preg_match('/Error .*in |Exception .*in /', $errorMessage) && strpos($errorMessage, $this->modulePath) === false) {
            return;
        }

        if (getenv('COMFINO_DEV_ENV') === 'TRUE' && getenv('COMFINO_FORCE_ERRORS_SENDING') !== 'TRUE') {
            $errorsSendingDisabled = true;
        } else {
            $errorsSendingDisabled = false;
        }

        $callEnv = array_merge($this->environment, [
            'caller' => $this->formatContext($this->extractCallerContext()),
        ]);

        $normalizedMessage = $this->normalizer->normalizeMessage($errorMessage);
        $normalizedTrace = $stackTrace !== null ? $this->normalizer->normalizeStackTrace($stackTrace) : null;

        $error = new ShopPluginError(
            $this->host,
            $this->platform,
            $this->pluginVersion,
            $this->platformVersion,
            $this->phpVersion,
            $category,
            $severity,
            $context,
            $errorCode,
            $normalizedMessage,
            $callEnv,
            ReportShopPluginError::extractApiEndpoint($apiRequestUrl),
            $apiRequestUrl,
            $apiRequest,
            $apiResponse,
            $normalizedTrace,
            time()
        );

        $logPrefix = "[{$category->value}][{$context->value}]";

        if ($errorsSendingDisabled || !$this->apiClient->sendLoggedError($error)) {
            $requestInfo = [];

            if ($apiRequestUrl !== null) {
                $requestInfo[] = "API URL: $apiRequestUrl";
            }

            if ($apiRequest !== null) {
                $requestInfo[] = "API request: $apiRequest";
            }

            if ($apiResponse !== null) {
                $requestInfo[] = "API response: $apiResponse";
            }

            if (count($requestInfo) > 0) {
                $errorMessage .= "\n" . implode("\n", $requestInfo);
            }

            if ($stackTrace !== null) {
                $errorMessage .= "\nStack trace: $stackTrace";
            }

            $this->logError($logPrefix, $errorMessage);
        }
    }

    /**
     * @param string $errorPrefix
     * @param string $errorMessage
     */
    public function logError($errorPrefix, $errorMessage): void
    {
        try {
            $this->getLogger()->error("$errorPrefix: $errorMessage");
        } catch (\Exception $e) {
            if (FileUtils::isWritable($this->logFilePath)) {
                FileUtils::append($this->logFilePath, "$errorPrefix: $errorMessage");
                FileUtils::append($this->logFilePath, "Logger error: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
            }
        }
    }

    /**
     * @param int $numLines
     * @return string
     */
    public function getErrorLog($numLines): string
    {
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
        return $this->clearLogFiles($this->logFilePath);
    }

    /**
     * @param \Throwable $exception
     * @return ErrorCategory
     */
    public static function classifyException($exception): ErrorCategory
    {
        return ErrorCategory::from((function () use ($exception) {
            switch (true) {
                case $exception instanceof \ArgumentCountError:
                    return ErrorCategory::ExceptionArgCount;
                case $exception instanceof \TypeError:
                    return ErrorCategory::ExceptionTypeError;
                case $exception instanceof \ParseError:
                    return ErrorCategory::ExceptionParseError;
                case $exception instanceof \DivisionByZeroError:
                    return ErrorCategory::ExceptionDivision;
                case $exception instanceof \LogicException:
                    return ErrorCategory::ExceptionLogicError;
                case $exception instanceof \Error:
                    return ErrorCategory::ExceptionError;
                default:
                    return ErrorCategory::ExceptionGeneric;
            }
        })());
    }

    /**
     * @param int $errorType
     * @param string $errorMessage
     * @param string $file
     * @param int $line
     * @return false
     */
    public function errorHandler($errorType, $errorMessage, $file, $line): bool
    {
        if (!($errorType & self::CATCHED_ERRORS_MASK)) {
            return false;
        }

        $errorLevel = $this->getErrorTypeName($errorType);

        $this->sendError(
            ErrorCategory::from(ErrorCategory::PhpError),
            $this->mapErrorSeverity($errorType),
            OperationContext::from(OperationContext::Unknown),
            (string) $errorType,
            "Error $errorLevel in $file:$line: $errorMessage"
        );

        return false;
    }

    /**
     * @param \Throwable $exception
     */
    public function exceptionHandler($exception): void
    {
        $exceptionClass = (new \ReflectionClass($exception))->getShortName();

        $this->sendError(
            self::classifyException($exception),
            ErrorSeverity::from(ErrorSeverity::Error),
            OperationContext::from(OperationContext::Unknown),
            $exceptionClass,
            sprintf('Exception %s in %s:%d: %s', $exceptionClass, $exception->getFile(), $exception->getLine(), $exception->getMessage()),
            null,
            null,
            null,
            $exception->getTraceAsString()
        );
    }

    public function init(): void
    {
        if (getenv('COMFINO_DEV_ENV') === 'TRUE' && getenv('COMFINO_FORCE_ERRORS_HANDLING') !== 'TRUE') {
            return;
        }

        static $initialized = false;

        if (!$initialized) {
            set_error_handler([$this, 'errorHandler'], self::CATCHED_ERRORS_MASK);
            set_exception_handler([$this, 'exceptionHandler']);
            register_shutdown_function([$this, 'shutdown']);

            $initialized = true;
        }
    }

    public function shutdown(): void
    {
        if (($error = error_get_last()) !== null && ($error['type'] & self::CATCHED_ERRORS_MASK)) {
            $errorLevel = $this->getErrorTypeName($error['type']);

            $this->sendError(
                ErrorCategory::from(ErrorCategory::PhpError),
                $this->mapErrorSeverity($error['type']),
                OperationContext::from(OperationContext::Unknown),
                (string) $error['type'],
                "Error $errorLevel in $error[file]:$error[line]: $error[message]"
            );
        }

        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * @param int $errorType
     * @return string
     */
    private function getErrorTypeName(int $errorType): string
    {
        return
            (($errorTypeName = array_key_exists($errorType, self::ERROR_TYPES) ? self::ERROR_TYPES[$errorType] : 'UNKNOWN') === 'UNKNOWN') &&
            (PHP_VERSION_ID < 70400) &&
            $errorType === E_STRICT
                ? 'E_STRICT'
                : $errorTypeName;
    }

    /**
     * @param int $errorType
     * @return ErrorSeverity
     */
    private function mapErrorSeverity(int $errorType): ErrorSeverity
    {
        return ErrorSeverity::from((function () use ($errorType) {
            switch ($errorType) {
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_RECOVERABLE_ERROR:
                case E_PARSE:
                case E_USER_ERROR:
                    return ErrorSeverity::Error;
                case E_WARNING:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                case E_USER_WARNING:
                    return ErrorSeverity::Warning;
                case E_NOTICE:
                case E_USER_NOTICE:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    return ErrorSeverity::Notice;
                default:
                    return ErrorSeverity::Error;
            }
        })());
    }

    /**
     * @return array<string,
     */
    private function extractCallerContext(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[2] ?? [];

        return [
            'class' => $caller['class'] ?? null,
            'function' => $caller['function'] ?? null,
            'file' => basename($caller['file'] ?? ''),
            'line' => $caller['line'] ?? 0,
        ];
    }

    /**
     * @return string
     */
    private function formatContext(array $context): string
    {
        if (!empty($context['class'])) {
            return sprintf(
                '[%s::%s@%s:%d]',
                substr(strrchr($context['class'], '\\'), 1),
                $context['function'],
                $context['file'],
                $context['line']
            );
        }

        return sprintf('[%s@%s:%d]', $context['function'], $context['file'], $context['line']);
    }
}
