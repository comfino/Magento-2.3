<?php

namespace Comfino\Common\Frontend;

final class PaywallRenderer extends FrontendRenderer
{
    /**
     * @param string $paywallContents
     * @param string $apiKey
     */
    public function getPaywallHash($paywallContents, $apiKey): string
    {
        return hash_hmac('sha3-256', $paywallContents, $apiKey);
    }

    /**
     * @return string[]
     */
    public function getStyles(): array
    {
        return ['paywall.css'];
    }

    /**
     * @return string[]
     */
    public function getScripts(): array
    {
        return ['paywall.js'];
    }
}
