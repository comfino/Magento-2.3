<?php

namespace Comfino\ComfinoGateway\Model\Ui;

use Comfino\Api\Dto\Payment\LoanTypeEnum;
use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Helper\PaywallAuthTokenGenerator;
use Comfino\Configuration\ConfigManager;
use Comfino\Configuration\SettingsManager;
use Comfino\FinancialProduct\ProductTypesListTypeEnum;
use Comfino\Order\OrderManager;
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
     * Auth token, loan amount, SDK URL, environment, and allowed product types are passed
     * via window.checkoutConfig to the JS renderer (comfino-method.js).
     * The SDK constructs the full paywall URL from authToken + loanAmount + environment.
     */
    public function getConfig(): array
    {
        $quote = $this->checkoutSession->getQuote();
        $loanAmount = (int) round($quote->getGrandTotal() * 100);

        // Compute allowed product types based on active category/value filters.
        // null  = no filters active (no restriction)
        // []    = all product types filtered out (paywall should be hidden)
        // [...] = filtered subset of product types
        $allowedProductTypes = null;

        try {
            $cart = OrderManager::getShopCart($quote);
            $types = SettingsManager::getAllowedProductTypes(
                ProductTypesListTypeEnum::LIST_TYPE_PAYWALL,
                $cart
            );

            if ($types !== null) {
                $allowedProductTypes = array_map(
                    static function (LoanTypeEnum $t): string { return (string) $t; },
                    $types
                );
            }
        } catch (\Throwable $e) {
            // Ignore filter errors - proceed without restriction
        }

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => true,
                    'pluginVersion' => $this->helper->getModuleVersion(),
                    'authToken' => $this->authTokenGenerator->generateAuthToken(),
                    'loanAmount' => $loanAmount,
                    'sdkScriptUrl' => ConfigManager::getSdkScriptUrl(),
                    'environment' => ConfigManager::isSandboxMode() ? 'sandbox' : 'production',
                    'allowedProductTypes' => $allowedProductTypes,
                ]
            ]
        ];
    }
}
