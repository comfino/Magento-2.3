<?php

namespace Comfino\ComfinoGateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class IframeUrlGenerator extends AbstractHelper
{
    private Data $configHelper;

    public function __construct(Context $context, Data $configHelper)
    {
        parent::__construct($context);

        $this->configHelper = $configHelper;
    }

    /**
     * Generate V3 Comfino API URL for paywall iframe.
     *
     * Uses the new simplified auth algorithm:
     *   payload = BE uint64(timestamp) | HMAC-SHA3-256(timestamp_bytes ∥ widgetKey, apiKey) | widgetKey
     *   auth = base64_encode(payload), URL-encoded
     *
     * loanAmount and productTypes are plain query parameters - NOT included in the signed payload.
     * Tokens expire after 15 minutes (enforced server-side).
     *
     * @param int $loanAmount Amount in grosz (1 PLN = 100 grosz)
     * @param string[]|null $productTypes Optional filter (e.g. ['PAY_LATER', 'INSTALLMENTS_ZERO_PERCENT'])
     */
    public function generatePaywallUrl(int $loanAmount, ?array $productTypes = null): string
    {
        $url = $this->configHelper->getApiHost() . '/v3/paywall'
            . '?auth=' . $this->generateAuthToken($this->configHelper->getApiKey(), $this->configHelper->getWidgetKey() ?? '')
            . '&loanAmount=' . $loanAmount;

        if (!empty($productTypes)) {
            $url .= '&types=' . rawurlencode(implode(',', $productTypes));
        }

        return $url;
    }

    /**
     * Build the V3 auth token.
     *
     * Payload layout (76 bytes):
     *   Bytes  0–7:  Unix timestamp, unsigned 64-bit big-endian
     *   Bytes  8–39: HMAC-SHA3-256( timestamp_bytes ∥ widgetKey_bytes ), keyed with apiKey
     *   Bytes 40–75: widgetKey as UTF-8 string (36-byte UUIDv4)
     */
    private function generateAuthToken(string $apiKey, string $widgetKey): string
    {
        $timestampBytes = pack('J', time());

        $hmac = hash_hmac('sha3-256', $timestampBytes . $widgetKey, $apiKey, true);

        return urlencode(base64_encode($timestampBytes . $hmac . $widgetKey));
    }
}
