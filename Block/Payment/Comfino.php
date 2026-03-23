<?php

namespace Comfino\ComfinoGateway\Block\Payment;

use Comfino\ComfinoGateway\Helper\IframeUrlGenerator;
use Comfino\Configuration\ConfigManager;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Comfino extends Template
{
    private IframeUrlGenerator $urlGenerator;
    private CheckoutSession $checkoutSession;

    public function __construct(
        Context $context,
        IframeUrlGenerator $urlGenerator,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->urlGenerator = $urlGenerator;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns full V3 paywall URL for the Comfino web frontend SDK.
     * Includes auth token (HMAC-SHA3-256 signed) and loanAmount as plain query params.
     * Iframe points directly to api-ecommerce.comfino.pl - NOT the shop domain.
     */
    public function getPaywallUrl(): string
    {
        $quote = $this->checkoutSession->getQuote();
        $loanAmount = (int) round($quote->getGrandTotal() * 100);

        return $this->urlGenerator->generatePaywallUrl($loanAmount);
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

    /**
     * Returns backend AJAX endpoint URL for persisting selected loan parameters.
     */
    public function getPaymentStateUrl(): string
    {
        return $this->getUrl('comfino/api/paymentstate');
    }
}