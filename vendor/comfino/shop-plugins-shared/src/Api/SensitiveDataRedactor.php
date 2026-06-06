<?php

declare(strict_types=1);

namespace Comfino\Api;

final class SensitiveDataRedactor
{
    private const SENSITIVE_KEY_FRAGMENTS = [
        'email',
        'phone',
        'taxid',
        'tax_id',
        'firstname',
        'first_name',
        'lastname',
        'last_name',
        'street',
        'postalcode',
        'postal_code',
        'zipcode',
        'zip_code',
        'housenumber',
        'house_number',
        'flatnumber',
        'flat_number',
        'buildingnumber',
        'building_number',
        'city',
        'ip',
        'ipaddress',
        'ip_address',
        'apikey',
        'api_key',
        'authorization',
        'password',
        'secret',
        'token',
        'credential',
        'pesel',
        'nip',
        'regon',
    ];

    private const REDACTED = '***REDACTED***';

    public static function redactJsonString(string $payload): string
    {
        if ($payload === '') {
            return '';
        }

        $decoded = json_decode($payload, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return sprintf('***NON-JSON-PAYLOAD (%d bytes)***', strlen($payload));
        }

        $redacted = self::redactStructure($decoded);
        $reencoded = json_encode($redacted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $reencoded === false ? self::REDACTED : $reencoded;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public static function redactStructure($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $result = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && self::isSensitiveKey($key)) {
                $result[$key] = $item === null ? null : self::REDACTED;

                continue;
            }

            $result[$key] = is_array($item) ? self::redactStructure($item) : $item;
        }

        return $result;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (strpos($normalized, $fragment) !== false) {
                return true;
            }
        }

        return false;
    }
}
