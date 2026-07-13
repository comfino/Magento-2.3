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

    public const MESSAGE_VERSION = 1;

    private const MESSAGE_VERSION_HEADER = 'Comfino-Message-Version';

    private const MIN_HASH_KEY_LENGTH = 16;

    private const MAX_ERROR_MESSAGE_LENGTH = 2000;

    private const MAX_URL_LENGTH = 2048;

    private const MAX_API_PAYLOAD_LENGTH = 16384;

    private const MAX_STACK_TRACE_LENGTH = 8192;

    private const MAX_ENVIRONMENT_ENTRIES = 50;

    private const MAX_ENVIRONMENT_VALUE_LENGTH = 200;

    private const PII_FIELDS = [
        'firstName', 'lastName', 'email', 'phone', 'taxId',
        'street', 'buildingNumber', 'apartmentNumber', 'postalCode', 'city',
    ];

    private const SENSITIVE_ENV_PATTERNS = [
        '/password/i',
        '/passwd/i',
        '/pwd/i',
        '/secret/i',
        '/token/i',
        '/api[_\-]?key/i',
        '/auth/i',
        '/credential/i',
        '/private/i',
        '/cert/i',
        '/salt/i',
        '/hash/i',
    ];

    public function __construct(ShopPluginError $shopPluginError, string $hashKey)
    {
        $this->shopPluginError = $shopPluginError;
        $this->hashKey = $hashKey;
        $this->setRequestMethod('POST');
        $this->setApiEndpointPath('log-plugin-error');
        $this->setRequestHeaders([self::MESSAGE_VERSION_HEADER => (string) self::MESSAGE_VERSION]);
    }

    /**
     * @param string|null $apiRequestUrl
     * @return string|null
     */
    public static function extractApiEndpoint($apiRequestUrl): ?string
    {
        if ($apiRequestUrl === null || $apiRequestUrl === '') {
            return null;
        }

        $path = parse_url($apiRequestUrl, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return null;
        }

        $path = trim($path, '/');

        return $path !== '' ? $path : null;
    }

    protected function prepareRequestBody(): ?array
    {
        $errorDetailsArray = [
            'host' => $this->shopPluginError->host,
            'platform' => $this->shopPluginError->platform,
            'plugin_version' => $this->shopPluginError->pluginVersion,
            'platform_version' => $this->shopPluginError->platformVersion,
            'php_version' => $this->shopPluginError->phpVersion,
            'category' => $this->shopPluginError->category->value,
            'severity' => $this->shopPluginError->severity->value,
            'context' => $this->shopPluginError->context->value,
            'environment' => self::sanitizeEnvironment($this->shopPluginError->environment),
            'error_code' => $this->shopPluginError->errorCode,
            'error_message' => self::filterPaths(
                self::truncate($this->shopPluginError->errorMessage, self::MAX_ERROR_MESSAGE_LENGTH)
            ),
            'api_endpoint' => $this->shopPluginError->apiEndpoint !== null
                ? self::truncate($this->shopPluginError->apiEndpoint, self::MAX_URL_LENGTH)
                : null,
            'api_request_url' => $this->shopPluginError->apiRequestUrl !== null
                ? self::truncate($this->shopPluginError->apiRequestUrl, self::MAX_URL_LENGTH)
                : null,
            'api_request' => $this->shopPluginError->apiRequest !== null
                ? self::truncate(
                    self::sanitizeJsonPayload($this->shopPluginError->apiRequest),
                    self::MAX_API_PAYLOAD_LENGTH
                )
                : null,
            'api_response' => $this->shopPluginError->apiResponse !== null
                ? self::truncate(
                    self::sanitizeJsonPayload($this->shopPluginError->apiResponse),
                    self::MAX_API_PAYLOAD_LENGTH
                )
                : null,
            'stack_trace' => $this->shopPluginError->stackTrace !== null
                ? self::filterStackTracePaths(
                    self::truncate($this->shopPluginError->stackTrace, self::MAX_STACK_TRACE_LENGTH)
                )
                : null,
            'occurred_at' => $this->shopPluginError->occurredAt,
        ];

        if (strlen($this->hashKey) < self::MIN_HASH_KEY_LENGTH) {
            throw new RequestValidationError(
                sprintf('Hash key must be at least %d characters long.', self::MIN_HASH_KEY_LENGTH)
            );
        }

        if (($errorDetails = gzcompress($this->serializer->serialize($errorDetailsArray), 4)) === false) {
            throw new RequestValidationError('Error report preparation failed.');
        }

        $encodedErrorDetails = base64_encode($errorDetails);
        $timestamp = time();

        return [
            'error_details' => $encodedErrorDetails,
            'timestamp' => $timestamp,
            'hash' => hash_hmac('sha3-256', $encodedErrorDetails . $timestamp, $this->hashKey),
        ];
    }

    private static function truncate(string $value, int $maxLength): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength - 3) . '...';
    }

    private static function sanitizeJsonPayload(string $payload): string
    {
        try {
            $data = json_decode($payload, true, 512, 0);
        } catch (\JsonException $exception) {
            return $payload;
        }

        if (!is_array($data)) {
            return $payload;
        }

        try {
            return json_encode(self::redactArray($data), 0);
        } catch (\JsonException $exception) {
            return $payload;
        }
    }

    /**
     * @return array<array-key,
     */
    private static function redactArray(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (in_array($key, self::PII_FIELDS, true)) {
                $value = '[REDACTED]';
            } elseif (is_array($value)) {
                $value = self::redactArray($value);
            }
        }

        return $data;
    }

    /**
     * @return array<string,
     */
    private static function sanitizeEnvironment(array $environment): array
    {
        $sanitized = [];

        foreach ($environment as $key => $value) {
            if (count($sanitized) >= self::MAX_ENVIRONMENT_ENTRIES) {
                break;
            }

            if (!is_string($key)) {
                continue;
            }

            foreach (self::SENSITIVE_ENV_PATTERNS as $pattern) {
                if (preg_match($pattern, $key)) {
                    $sanitized[$key] = '[REDACTED]';

                    continue 2;
                }
            }

            if (is_array($value) || is_object($value)) {
                $sanitized[$key] = '[COMPLEX]';
            } else {
                $sanitized[$key] = self::truncate((string) $value, self::MAX_ENVIRONMENT_VALUE_LENGTH);
            }
        }

        return $sanitized;
    }

    private static function filterStackTracePaths(string $stackTrace): string
    {
        return preg_replace_callback(
            '/^(#\d+\s+)([^(]+)(\(\d+\))/mu',
            static function (array $matches): string {
                $path = rtrim($matches[2]);

                if (strpos($path, '/') !== false || strpos($path, '\\') !== false) {
                    return $matches[1] . basename(str_replace('\\', '/', $path)) . $matches[3];
                }

                return $matches[0];
            },
            $stackTrace
        ) ?? $stackTrace;
    }

    private static function filterPaths(string $text): string
    {
        return preg_replace('~(?<![:/\w])(?:/[^/\s:()]+)+/([^/\s:()]+)~u', '$1', $text) ?? $text;
    }
}
