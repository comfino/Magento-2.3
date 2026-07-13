<?php

declare(strict_types=1);

namespace Comfino\Extended\Auth;

final class PaywallAuthTokenGenerator
{
    public static function generateAuthToken(string $widgetKey, string $apiKey): string
    {
        $timestampBytes = pack('J', time());
        $hmac = hash_hmac('sha3-256', $timestampBytes . $widgetKey, $apiKey, true);

        return base64_encode($timestampBytes . $hmac . $widgetKey);
    }

    /**
     * @param string $widgetKey
     * @param string $signingKey
     */
    public static function generateLoggingToken(string $widgetKey, string $signingKey): string
    {
        $versionByte = pack('C', 1);
        $timestampBytes = pack('J', time());
        $hmac = hash_hmac(
            'sha3-256',
            'comfino-fe-log:v1' . $versionByte . $timestampBytes . $widgetKey,
            $signingKey,
            true
        );

        return base64_encode($versionByte . $timestampBytes . $hmac . $widgetKey);
    }
}
