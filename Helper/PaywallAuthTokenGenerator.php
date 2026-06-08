<?php

namespace Comfino\ComfinoGateway\Helper;

use Comfino\Configuration\ConfigManager;
use Magento\Framework\App\Helper\AbstractHelper;

class PaywallAuthTokenGenerator extends AbstractHelper
{
    /**
     * Generate V3 Comfino paywall auth token.
     *
     * Returns a raw base64-encoded payload (not URL-encoded).
     * The SDK constructs the full paywall URL from this token and the configured environment:
     *   https://api-ecommerce.comfino.pl/v3/paywall?auth=<token>&loanAmount=<amount>
     *
     * Payload layout (76 bytes):
     *   Bytes 0–7: Unix timestamp, unsigned 64-bit big-endian
     *   Bytes 8–39: HMAC-SHA3-256(timestamp_bytes ∥ widgetKey_bytes), keyed with apiKey
     *   Bytes 40–75: widgetKey as UTF-8 string (36-byte UUIDv4)
     *
     * Tokens expire after 15 minutes (enforced server-side).
     */
    public function generateAuthToken(): string
    {
        $timestampBytes = pack('J', time());
        $apiKey = ConfigManager::getApiKey() ?? '';
        $widgetKey = ConfigManager::getWidgetKey() ?? '';
        $hmac = hash_hmac('sha3-256', $timestampBytes . $widgetKey, $apiKey, true);

        return base64_encode($timestampBytes . $hmac . $widgetKey);
    }

    /**
     * Generate a versioned, domain-separated auth token for the Comfino frontend error reporting endpoint.
     *
     * Must stay byte-compatible with Comfino\Auth\FrontendLogAuthKeyGenerator in the Comfino Web SDK
     * backend, since a single server-side validator accepts tokens from all plugins.
     *
     * Payload layout (77 bytes):
     *   Byte 0: Version, unsigned 8-bit integer (currently 1)
     *   Bytes 1–8: Unix timestamp, unsigned 64-bit big-endian
     *   Bytes 9–40: HMAC-SHA3-256("comfino-fe-log:v1" ∥ version ∥ timestamp ∥ widgetKey), keyed with apiKey
     *   Bytes 41–76: widgetKey as UTF-8 string (36-byte UUIDv4)
     *
     * The "comfino-fe-log:v1" domain prefix makes this token non-interchangeable with the paywall auth token.
     */
    public function generateLoggingToken(): string
    {
        $versionByte = pack('C', 1);
        $timestampBytes = pack('J', time());
        $apiKey = ConfigManager::getApiKey() ?? '';
        $widgetKey = ConfigManager::getWidgetKey() ?? '';
        $hmac = hash_hmac('sha3-256', 'comfino-fe-log:v1' . $versionByte . $timestampBytes . $widgetKey, $apiKey, true);

        return base64_encode($versionByte . $timestampBytes . $hmac . $widgetKey);
    }
}
