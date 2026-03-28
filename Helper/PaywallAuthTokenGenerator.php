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
     *   Bytes  0–7:  Unix timestamp, unsigned 64-bit big-endian
     *   Bytes  8–39: HMAC-SHA3-256( timestamp_bytes ∥ widgetKey_bytes ), keyed with apiKey
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
}