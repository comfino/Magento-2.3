<?php

namespace Comfino\ComfinoGateway\Model\Ui;

use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Helper\IframeUrlGenerator;
use Comfino\Configuration\ConfigManager;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'comfino';

    protected Data $helper;
    private IframeUrlGenerator $urlGenerator;
    private CheckoutSession $checkoutSession;

    public function __construct(
        Data $helper,
        IframeUrlGenerator $urlGenerator,
        CheckoutSession $checkoutSession
    ) {
        $this->helper = $helper;
        $this->urlGenerator = $urlGenerator;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns checkout configuration for Comfino payment method.
     * Paywall URL, SDK URL, and environment are passed via window.checkoutConfig to the JS renderer.
     */
    public function getConfig(): array
    {
        $quote = $this->checkoutSession->getQuote();
        $loanAmount = (int) round($quote->getGrandTotal() * 100);

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => true,
                    'pluginVersion' => $this->helper->getModuleVersion(),
                    'paywallUrl' => $this->urlGenerator->generatePaywallUrl($loanAmount),
                    'sdkScriptUrl' => ConfigManager::getSdkScriptUrl(),
                    'environment' => ConfigManager::isSandboxMode() ? 'sandbox' : 'production',
                ]
            ]
        ];
    }
}
