<?php

namespace Comfino\ComfinoGateway\Model\Ui;

use Comfino\Api\ApiClient;
use Comfino\Api\Dto\Payment\LoanTypeEnum;
use Comfino\Common\Shop\Cart;
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
        ApiClient::pinCheckoutTrackId();

        $quote = $this->checkoutSession->getQuote();
        $loanAmount = (int) round($quote->getGrandTotal() * 100);

        // Compute allowed product types based on active category/value filters.
        // null  = no filters active (no restriction)
        // []    = all product types filtered out (paywall should be hidden)
        // [...] = filtered subset of product types
        $allowedProductTypes = null;
        $paywallCart = null;

        try {
            $cart = OrderManager::getShopCart($quote);
            $paywallCart = self::buildPaywallCart($cart);
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

        /* Optional paywall-data enrichments. Each is best-effort: a failure here must not break checkout,
           and the SDK treats every one of these fields as optional, falling back to loanAmount alone. */
        $currency = $quote->getQuoteCurrencyCode() ?: 'PLN';

        try {
            $productTypeNames = SettingsManager::getProductTypes(ProductTypesListTypeEnum::LIST_TYPE_PAYWALL) ?: null;
        } catch (\Throwable $e) {
            $productTypeNames = null;
        }

        try {
            $paymentMethodAuth = ConfigManager::getPaywallLogoAuthHash();
        } catch (\Throwable $e) {
            $paymentMethodAuth = '';
        }

        try {
            // Product-type => creditor-codes map; drives the creditor-logo row on the payment-method tile.
            $creditors = SettingsManager::getCreditors() ?: null;
        } catch (\Throwable $e) {
            $creditors = null;
        }

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => true,
                    'pluginVersion' => $this->helper->getModuleVersion(),
                    'authToken' => $this->authTokenGenerator->generateAuthToken(),
                    'loggingToken' => $this->authTokenGenerator->generateLoggingToken(),
                    'trackId' => ApiClient::getInstance()->getTrackId(),
                    'loanAmount' => $loanAmount,
                    'sdkScriptUrl' => ConfigManager::getSdkScriptUrl(),
                    'environment' => ConfigManager::isSandboxMode() ? 'sandbox' : 'production',
                    'paymentMethodLabel' => ConfigManager::getConfigurationValue('COMFINO_PAYMENT_TEXT') ?: null,
                    'defaultLogoUrl' => ConfigManager::getDefaultLogoUrl(),
                    'allowedProductTypes' => $allowedProductTypes,
                    'cart' => $paywallCart,
                    'paywallSettings' => [
                        'language' => $this->helper->getShopLanguage(),
                        'currency' => $currency,
                    ],
                    'productTypeNames' => $productTypeNames,
                    'paymentMethodAuth' => $paymentMethodAuth,
                    'creditors' => $creditors,
                ]
            ]
        ];
    }

    /**
     * Serializes the shared-library Cart into the flat array shape the paywall iframe expects.
     * All monetary values are already expressed in minor units (grosze) by the Cart DTO.
     *
     * @return array<string, mixed>|null null when the cart has no items
     */
    private static function buildPaywallCart(Cart $cart): ?array
    {
        $products = [];

        foreach ($cart->getCartItems() as $cartItem) {
            $product = $cartItem->getProduct();

            $products[] = [
                'name' => $product->getName(),
                'quantity' => $cartItem->getQuantity(),
                'price' => $product->getPrice(),
                'netPrice' => $product->getNetPrice() ?? $product->getPrice(),
                'vatRate' => $product->getTaxRate() ?? 0,
                'vatAmount' => $product->getTaxValue() ?? 0,
                'category' => $product->getCategory() ?? '',
            ];
        }

        if ($products === []) {
            return null;
        }

        return [
            'totalAmount' => $cart->getTotalValue(),
            'deliveryCost' => $cart->getDeliveryCost(),
            'deliveryNetCost' => $cart->getDeliveryNetCost() ?? 0,
            'deliveryCostVatRate' => $cart->getDeliveryTaxRate() ?? 0,
            'deliveryCostVatAmount' => $cart->getDeliveryTaxValue() ?? 0,
            'products' => $products,
        ];
    }
}
