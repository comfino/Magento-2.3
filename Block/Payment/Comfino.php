<?php

namespace Comfino\ComfinoGateway\Block\Payment;

use Comfino\ComfinoGateway\Helper\PaywallAuthTokenGenerator;
use Comfino\Configuration\ConfigManager;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Comfino extends Template
{
    private PaywallAuthTokenGenerator $authTokenGenerator;
    private CheckoutSession $checkoutSession;

    public function __construct(
        Context $context,
        PaywallAuthTokenGenerator $authTokenGenerator,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->authTokenGenerator = $authTokenGenerator;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns the V3 paywall auth token (raw base64, not URL-encoded).
     * The SDK constructs the full paywall URL from this token + environment.
     */
    public function getAuthToken(): string
    {
        return $this->authTokenGenerator->generateAuthToken();
    }

    /**
     * Returns the cart grand total in grosze (1 PLN = 100 grosze).
     */
    public function getLoanAmount(): int
    {
        return (int) round($this->checkoutSession->getQuote()->getGrandTotal() * 100);
    }

    /**
     * Returns Comfino web frontend SDK URL from CDN (production or sandbox).
     */
    public function getSdkScriptUrl(): string
    {
        return ConfigManager::getSdkScriptUrl();
    }

    /**
     * Returns SDK environment string for sdk.init().
     */
    public function getEnvironment(): string
    {
        return ConfigManager::isSandboxMode() ? 'sandbox' : 'production';
    }
}