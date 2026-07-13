<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Log;

final class ErrorMessageNormalizer
{
    private const PATTERN_COMM_TIMESTAMP = '/Communication error \[\d+]:/';
    private const REPLACE_COMM_TIMESTAMP = 'Communication error:';

    private const PATTERN_TIMEOUT = '/after \d+ milliseconds/';
    private const REPLACE_TIMEOUT = 'after {N}ms';

    private const PATTERN_BYTES = '/with \d+ bytes( received)?/';
    private const REPLACE_BYTES = 'with {N} bytes';

    private const PATTERN_CALLED_IN = '/, called in .+? on line \d+/s';

    /**
     * @var string[]
     */
    private const PLUGIN_ROOT_PATTERNS = [
        '~/\S*/wp-content/plugins/comfino-payment-gateway/~',
        '~/\S*/modules/comfino/~',
        '~/\S*/app/code/Comfino/ComfinoGateway/~',
        '~/\S*/vendor/comfino/[^/\s]+/~',
    ];

    /**
     * @param string $message
     * @return string
     */
    public function normalizeMessage(string $message): string
    {
        $message = self::pregReplace(self::PATTERN_COMM_TIMESTAMP, self::REPLACE_COMM_TIMESTAMP, $message);
        $message = self::pregReplace(self::PATTERN_TIMEOUT, self::REPLACE_TIMEOUT, $message);
        $message = self::pregReplace(self::PATTERN_BYTES, self::REPLACE_BYTES, $message);
        $message = self::pregReplace(self::PATTERN_CALLED_IN, '', $message);

        return trim($message);
    }

    /**
     * @param string $stackTrace
     * @return string
     */
    public function normalizeStackTrace(string $stackTrace): string
    {
        foreach (self::PLUGIN_ROOT_PATTERNS as $pattern) {
            $stackTrace = self::pregReplace($pattern, '', $stackTrace);
        }

        return $stackTrace;
    }

    private static function pregReplace(string $pattern, string $replacement, string $subject): string
    {
        return is_string($result = preg_replace($pattern, $replacement, $subject)) ? $result : $subject;
    }
}
