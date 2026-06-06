<?php

declare(strict_types=1);

namespace Comfino\Api;

use Comfino\Api\Exception\RequestValidationError;

final class UrlValidator
{
    /**
     * @throws RequestValidationError
     */
    public static function assertValidCallbackUrl(string $url, string $headerName): void
    {
        if (preg_match('/[\r\n\x00]/', $url) === 1) {
            throw new RequestValidationError(
                sprintf('Callback URL for "%s" contains illegal control characters.', $headerName)
            );
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RequestValidationError(
                sprintf('Callback URL for "%s" is not a valid URL.', $headerName)
            );
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!is_string($scheme) || !in_array(strtolower($scheme), ['http', 'https'], true)) {
            throw new RequestValidationError(
                sprintf('Callback URL for "%s" must use http or https scheme.', $headerName)
            );
        }
    }
}
