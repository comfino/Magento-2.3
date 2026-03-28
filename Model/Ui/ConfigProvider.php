<?php

namespace Comfino\ComfinoGateway\Model\Ui;

use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Helper\PaywallAuthTokenGenerator;
use Comfino\Configuration\ConfigManager;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'comfino';

    protected Data $helper;
    private PaywallAuthTokenGenerator $authTokenGenerator;
    private CheckoutSession $checkoutSession;

    public function __construct(
        Data $helper,
        PaywallAuthTokenGenerator $authTokenGenerator,
        CheckoutSession $checkoutSession
    ) {
        $this->helper = $helper;
        $this->authTokenGenerator = $authTokenGenerator;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns checkout configuration for Comfino payment method.
     * Auth token, loan amount, SDK URL, and environment are passed via window.checkoutConfig
     * to the JS renderer (comfino-method.js), which populates window.ComfinoPaywallData.
     * The SDK constructs the full paywall URL from authToken + loanAmount + environment.
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
                    'authToken' => $this->authTokenGenerator->generateAuthToken(),
                    'loanAmount' => $loanAmount,
                    'sdkScriptUrl' => ConfigManager::getSdkScriptUrl(),
                    'environment' => ConfigManager::isSandboxMode() ? 'sandbox' : 'production',
                ]
            ]
        ];
    }
}