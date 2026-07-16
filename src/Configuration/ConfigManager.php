<?php

namespace Comfino\Configuration;

use Comfino\Api\ApiClient;
use Comfino\CategoryTree\BuildStrategy;
use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Helper\PaywallAuthTokenGenerator;
use Comfino\Common\Backend\ConfigurationManager;
use Comfino\Common\Frontend\FrontendHelper;
use Comfino\Common\Frontend\ProductWidgetScriptHelper;
use Comfino\Common\Shop\Order\StatusManager;
use Comfino\Common\Shop\Product\CategoryTree;
use Comfino\Extended\Api\Serializer\Json as JsonSerializer;
use Comfino\FinancialProduct\ProductTypesListTypeEnum;
use Comfino\Frontend\AbstractShopEnvironmentBuilder;
use Comfino\Order\OrderManager;
use Comfino\Order\ShopStatusManager;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Facade over Comfino\Common\Backend\ConfigurationManager for Magento.
 *
 * @see ConfigurationManager
 */
final class ConfigManager
{
    private const COMFINO_SDK_CDN_PRODUCTION = 'https://sdk.comfino.pl';
    private const COMFINO_SDK_CDN_SANDBOX = 'https://sdk.craty.pl';

    public const CONFIG_OPTIONS = [
        'payment_settings' => [
            'COMFINO_API_KEY' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_PAYMENT_TEXT' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_MINIMAL_CART_AMOUNT' => ConfigurationManager::OPT_VALUE_TYPE_FLOAT,
            'COMFINO_USE_ORDER_REFERENCE' => ConfigurationManager::OPT_VALUE_TYPE_BOOL,
        ],
        'sale_settings' => [
            'COMFINO_PRODUCT_CATEGORY_FILTERS' => ConfigurationManager::OPT_VALUE_TYPE_JSON,
        ],
        'widget_settings' => [
            'COMFINO_WIDGET_ENABLED' => ConfigurationManager::OPT_VALUE_TYPE_BOOL,
            'COMFINO_WIDGET_KEY' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_WIDGET_PRICE_SELECTOR' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_WIDGET_PRICE_ATTRIBUTE' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_WIDGET_TARGET_SELECTOR' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_WIDGET_PRICE_OBSERVER_SELECTOR' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_WIDGET_PRICE_OBSERVER_LEVEL' => ConfigurationManager::OPT_VALUE_TYPE_INT,
            'COMFINO_WIDGET_TYPE' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_WIDGET_OFFER_TYPES' => ConfigurationManager::OPT_VALUE_TYPE_STRING_ARRAY,
            'COMFINO_WIDGET_EMBED_METHOD' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_WIDGET_SHOW_PROVIDER_LOGOS' => ConfigurationManager::OPT_VALUE_TYPE_BOOL,
            'COMFINO_WIDGET_CUSTOM_BANNER_CSS_URL' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_WIDGET_CUSTOM_CALCULATOR_CSS_URL' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
        ],
        'developer_settings' => [
            'COMFINO_IS_SANDBOX' => ConfigurationManager::OPT_VALUE_TYPE_BOOL,
            'COMFINO_SANDBOX_API_KEY' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_DEBUG' => ConfigurationManager::OPT_VALUE_TYPE_BOOL,
            'COMFINO_SERVICE_MODE' => ConfigurationManager::OPT_VALUE_TYPE_BOOL,
            'COMFINO_DEV_ENV_VARS' => ConfigurationManager::OPT_VALUE_TYPE_BOOL,
        ],
        'hidden_settings' => [
            'COMFINO_CAT_FILTER_AVAIL_PROD_TYPES' => ConfigurationManager::OPT_VALUE_TYPE_STRING_ARRAY,
            'COMFINO_IGNORED_STATUSES' => ConfigurationManager::OPT_VALUE_TYPE_STRING_ARRAY,
            'COMFINO_FORBIDDEN_STATUSES' => ConfigurationManager::OPT_VALUE_TYPE_STRING_ARRAY,
            'COMFINO_STATUS_MAP' => ConfigurationManager::OPT_VALUE_TYPE_JSON,
            'COMFINO_API_CONNECT_TIMEOUT' => ConfigurationManager::OPT_VALUE_TYPE_INT,
            'COMFINO_API_TIMEOUT' => ConfigurationManager::OPT_VALUE_TYPE_INT,
            'COMFINO_API_CONNECT_NUM_ATTEMPTS' => ConfigurationManager::OPT_VALUE_TYPE_INT,
            'COMFINO_PROD_CAT_CACHE_TTL' => ConfigurationManager::OPT_VALUE_TYPE_INT,
            'COMFINO_INITIAL_ORDER_STATUS' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_ERROR_LOGGING_ACCESS_TOKEN' => ConfigurationManager::OPT_VALUE_TYPE_STRING,
            'COMFINO_ERROR_LOGGING_ACCESS_TOKEN_EXPIRES_AT' => ConfigurationManager::OPT_VALUE_TYPE_INT,
        ],
    ];

    public const ACCESSIBLE_CONFIG_OPTIONS = [
        // Payment settings
        'COMFINO_PAYMENT_TEXT',
        'COMFINO_MINIMAL_CART_AMOUNT',
        'COMFINO_USE_ORDER_REFERENCE',
        // Sale settings
        'COMFINO_PRODUCT_CATEGORY_FILTERS',
        // Widget settings
        'COMFINO_WIDGET_ENABLED',
        'COMFINO_WIDGET_KEY',
        'COMFINO_WIDGET_PRICE_SELECTOR',
        'COMFINO_WIDGET_PRICE_ATTRIBUTE',
        'COMFINO_WIDGET_TARGET_SELECTOR',
        'COMFINO_WIDGET_PRICE_OBSERVER_SELECTOR',
        'COMFINO_WIDGET_PRICE_OBSERVER_LEVEL',
        'COMFINO_WIDGET_TYPE',
        'COMFINO_WIDGET_OFFER_TYPES',
        'COMFINO_WIDGET_EMBED_METHOD',
        'COMFINO_WIDGET_SHOW_PROVIDER_LOGOS',
        'COMFINO_WIDGET_CUSTOM_BANNER_CSS_URL',
        'COMFINO_WIDGET_CUSTOM_CALCULATOR_CSS_URL',
        // Developer settings
        'COMFINO_IS_SANDBOX',
        'COMFINO_DEBUG',
        'COMFINO_SERVICE_MODE',
        'COMFINO_DEV_ENV_VARS',
        // Hidden settings
        'COMFINO_CAT_FILTER_AVAIL_PROD_TYPES',
        'COMFINO_IGNORED_STATUSES',
        'COMFINO_FORBIDDEN_STATUSES',
        'COMFINO_STATUS_MAP',
        'COMFINO_API_CONNECT_TIMEOUT',
        'COMFINO_API_TIMEOUT',
        'COMFINO_API_CONNECT_NUM_ATTEMPTS',
        'COMFINO_PROD_CAT_CACHE_TTL',
        'COMFINO_INITIAL_ORDER_STATUS',
    ];

    private static ?ConfigurationManager $configurationManager = null;
    /** @var int[]|null */
    private static ?array $availConfigOptions = null;

    public static function getInstance(): ConfigurationManager
    {
        if (self::$configurationManager === null) {
            self::$configurationManager = ConfigurationManager::getInstance(
                self::getAvailableConfigOptions(),
                self::ACCESSIBLE_CONFIG_OPTIONS,
                ConfigurationManager::OPT_SERIALIZE_ARRAYS,
                new StorageAdapter(
                    ObjectManager::getInstance()->get(ScopeConfigInterface::class),
                    ObjectManager::getInstance()->get(WriterInterface::class),
                    ObjectManager::getInstance()->get(TypeListInterface::class)
                ),
                new JsonSerializer()
            );
        }

        return self::$configurationManager;
    }

    /**
     * @param string[]|null $selectedEnvFields
     *
     * @return array
     */
    public static function getEnvironmentInfo(?array $selectedEnvFields = null): array
    {
        /** @var Data $dataHelper */
        $dataHelper = ObjectManager::getInstance()->get(Data::class);

        $envFields = [
            'plugin_version' => $dataHelper->getModuleVersion(),
            'plugin_build_ts' => Data::BUILD_TS,
            'shop_version' => $dataHelper->getShopVersion(),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'n/a',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'n/a',
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'n/a',
            'database_version' => $dataHelper->getDatabaseVersion(),
        ];

        if (empty($selectedEnvFields)) {
            return $envFields;
        }

        return array_intersect_key($envFields, array_flip($selectedEnvFields));
    }

    public static function getCategoriesTree(): CategoryTree
    {
        /** @var CategoryTree $categoriesTree */
        static $categoriesTree = null;

        if ($categoriesTree === null) {
            $categoriesTree = new CategoryTree(new BuildStrategy());
        }

        return $categoriesTree;
    }

    public static function getConfigurationValue(string $optionName, $defaultValue = null)
    {
        return self::getInstance()->getConfigurationValue($optionName) ?? $defaultValue;
    }

    public static function isSandboxMode(): bool
    {
        return self::getInstance()->getConfigurationValue('COMFINO_IS_SANDBOX') ?? false;
    }

    public static function isWidgetEnabled(): bool
    {
        return self::getInstance()->getConfigurationValue('COMFINO_WIDGET_ENABLED') ?? false;
    }

    public static function isDebugMode(): bool
    {
        return self::getInstance()->getConfigurationValue('COMFINO_DEBUG') ?? false;
    }

    public static function isServiceMode(): bool
    {
        return self::getInstance()->getConfigurationValue('COMFINO_SERVICE_MODE') ?? false;
    }

    public static function isUseOrderReference(): bool
    {
        return self::getInstance()->getConfigurationValue('COMFINO_USE_ORDER_REFERENCE') ?? false;
    }

    public static function useDevEnvVars(): bool
    {
        return getenv('COMFINO_DEV_ENV') === 'TRUE'
            && self::getInstance()->getConfigurationValue('COMFINO_DEV_ENV_VARS') ?? false;
    }

    public static function getApiHost(?string $apiHost = null): ?string
    {
        if (self::useDevEnvVars() && getenv('COMFINO_DEV_API_HOST')) {
            return getenv('COMFINO_DEV_API_HOST');
        }

        return $apiHost;
    }

    public static function getSdkCdnBaseUrl(): string
    {
        if (self::useDevEnvVars() && getenv('COMFINO_DEV_SDK_CDN_BASE_URL')) {
            return getenv('COMFINO_DEV_SDK_CDN_BASE_URL');
        }

        return self::isSandboxMode() ? self::COMFINO_SDK_CDN_SANDBOX : self::COMFINO_SDK_CDN_PRODUCTION;
    }

    public static function getCheckoutCssUrl(): string
    {
        return self::getSdkCdnBaseUrl() . '/checkout/v1/css/comfino-item-gate-magento.css';
    }

    /**
     * CDN URL of the single, SDK-hosted Comfino brand logo used as the default payment-tile placeholder across all
     * shop plugins/platforms. Rendered by the KO template as the tile's initial logo; the SDK renderer adopts it and
     * swaps its `src` at runtime (to the auth-gated API Comfino logo). Hosting it centrally on the SDK CDN keeps the
     * asset controllable without plugin updates.
     */
    public static function getDefaultLogoUrl(): string
    {
        if (self::useDevEnvVars() && getenv('COMFINO_DEV_DEFAULT_LOGO_URL')) {
            return getenv('COMFINO_DEV_DEFAULT_LOGO_URL');
        }

        return self::getSdkCdnBaseUrl() . '/images/comfino/comfino_logo.svg';
    }

    public static function getSdkScriptUrl(): string
    {
        if (self::useDevEnvVars() && getenv('COMFINO_DEV_SDK_SCRIPT_URL')) {
            return getenv('COMFINO_DEV_SDK_SCRIPT_URL');
        }

        return self::getSdkCdnBaseUrl() . '/sdk/v1/comfino-sdk.min.js';
    }

    /**
     * CDN URL of the Magento product-page widget script served from the SDK host at /product/v1/. The classic-IIFE
     * script reads the `#comfino-widget-config` JSON block and calls sdk.bootstrapWidget().
     */
    public static function getProductWidgetScriptUrl(): string
    {
        return self::getSdkCdnBaseUrl() . '/product/v1/comfino-magento-widget.min.js';
    }

    /**
     * Assembles the product-page widget config consumed by the CDN product widget script — the WidgetConfig contract
     * emitted into the `#comfino-widget-config` JSON block. Filtered against the shared `ProductWidgetScriptHelper::WIDGET_CONFIG_KEYS`
     * allowlist, which also drops nulls so omitted options fall through to the SDK / CDN-profile defaults.
     *
     * @return array<string, mixed>
     */
    public static function getWidgetConfig(?int $productId): array
    {
        $settings = self::getConfigurationValues(
            'widget_settings',
            [
                'COMFINO_WIDGET_KEY',
                'COMFINO_WIDGET_PRICE_SELECTOR',
                'COMFINO_WIDGET_PRICE_ATTRIBUTE',
                'COMFINO_WIDGET_TARGET_SELECTOR',
                'COMFINO_WIDGET_PRICE_OBSERVER_SELECTOR',
                'COMFINO_WIDGET_PRICE_OBSERVER_LEVEL',
                'COMFINO_WIDGET_TYPE',
                'COMFINO_WIDGET_OFFER_TYPES',
                'COMFINO_WIDGET_EMBED_METHOD',
                'COMFINO_WIDGET_SHOW_PROVIDER_LOGOS',
                'COMFINO_WIDGET_CUSTOM_BANNER_CSS_URL',
                'COMFINO_WIDGET_CUSTOM_CALCULATOR_CSS_URL',
            ]
        );

        $variables = self::getWidgetVariables($productId);

        $notNull = static function ($value) {
            return $value === 'null' ? null : $value;
        };

        // Magento reports the product price as a major-unit float; the SDK expects grosze (smallest unit).
        $priceValue = $notNull($variables['PRODUCT_PRICE'] ?? null);
        $price = $priceValue === null ? null : (int) round(((float) $priceValue) * 100);

        // COMFINO_WIDGET_OFFER_TYPES may be a string[] or a comma-separated string depending on storage.
        $rawOfferTypes = $settings['COMFINO_WIDGET_OFFER_TYPES'] ?? null;
        $offerTypes = is_array($rawOfferTypes)
            ? array_values($rawOfferTypes)
            : array_values(array_filter(array_map('trim', explode(',', (string) $rawOfferTypes)), 'strlen'));

        $config = [
            'sdkScriptUrl' => self::getSdkScriptUrl(),
            'environment' => self::isSandboxMode() ? 'sandbox' : 'production',
            'widgetKey' => $settings['COMFINO_WIDGET_KEY'] ?? null,
            'loggingToken' => $variables['LOGGING_TOKEN'] ?? null,
            'trackId' => $variables['TRACK_ID'] ?? null,
            'widgetTargetSelector' => $settings['COMFINO_WIDGET_TARGET_SELECTOR'] ?? null,
            'priceSelector' => $settings['COMFINO_WIDGET_PRICE_SELECTOR'] ?? null,
            'priceAttribute' => ($settings['COMFINO_WIDGET_PRICE_ATTRIBUTE'] ?? '') ?: null,
            'priceObserverSelector' => ($settings['COMFINO_WIDGET_PRICE_OBSERVER_SELECTOR'] ?? '') ?: null,
            'priceObserverLevel' => (int) ($settings['COMFINO_WIDGET_PRICE_OBSERVER_LEVEL'] ?? 0),
            'embedMethod' => $settings['COMFINO_WIDGET_EMBED_METHOD'] ?? null,
            'widgetType' => $settings['COMFINO_WIDGET_TYPE'] ?? null,
            'offerTypes' => $offerTypes !== [] ? $offerTypes : null,
            'showProviderLogos' => (bool) ($settings['COMFINO_WIDGET_SHOW_PROVIDER_LOGOS'] ?? false),
            'hasPriceInput' => false,
            'bannerCssUrl' => ($settings['COMFINO_WIDGET_CUSTOM_BANNER_CSS_URL'] ?? '') ?: null,
            'calculatorCssUrl' => ($settings['COMFINO_WIDGET_CUSTOM_CALCULATOR_CSS_URL'] ?? '') ?: null,
            'price' => $price,
            'productId' => $notNull($variables['PRODUCT_ID'] ?? null),
            'availableProductTypes' => $variables['AVAILABLE_PRODUCT_TYPES'] ?? null,
            'productCartDetails' => $notNull($variables['PRODUCT_CART_DETAILS'] ?? null),
            'language' => $variables['LANGUAGE'] ?? null,
            'currency' => $variables['CURRENCY'] ?? null,
            'shopEnvironment' => array_merge(
                ObjectManager::getInstance()->get(AbstractShopEnvironmentBuilder::class)
                    ->buildForFrontend(['type' => 'product']),
                [
                    'language' => $variables['LANGUAGE'] ?? 'pl',
                    'currency' => $variables['CURRENCY'] ?? 'PLN',
                ]
            ),
        ];

        return ProductWidgetScriptHelper::buildConfig($config);
    }

    public static function getLogoUrl(): string
    {
        /** @var Data $dataHelper */
        $dataHelper = ObjectManager::getInstance()->get(Data::class);

        return self::getApiHost(ApiClient::getInstance()->getApiHost()) . '/v1/get-logo-url?auth='
            . FrontendHelper::getLogoAuthHash('MG', $dataHelper->getShopVersion(), $dataHelper->getModuleVersion(), Data::BUILD_TS);
    }

    /**
     * HMAC-SHA3-256 authentication hash for the paywall creditor-logo endpoint, consumed by the SDK
     * (bootstrapPaywall paymentMethodItem.auth). Mirrors getLogoUrl() but binds the api/widget keys too.
     */
    public static function getPaywallLogoAuthHash(): string
    {
        /** @var Data $dataHelper */
        $dataHelper = ObjectManager::getInstance()->get(Data::class);

        return FrontendHelper::getPaywallLogoAuthHashRaw(
            'MG',
            $dataHelper->getShopVersion(),
            $dataHelper->getModuleVersion(),
            (string) self::getApiKey(),
            (string) self::getWidgetKey(),
            Data::BUILD_TS
        );
    }

    public static function getApiKey(): ?string
    {
        return self::isSandboxMode()
            ? self::getConfigurationValue('COMFINO_SANDBOX_API_KEY')
            : self::getConfigurationValue('COMFINO_API_KEY');
    }

    public static function getWidgetKey(): ?string
    {
        return self::getConfigurationValue('COMFINO_WIDGET_KEY');
    }

    public static function getErrorLoggingAccessToken(): string
    {
        return (string) (self::getConfigurationValue('COMFINO_ERROR_LOGGING_ACCESS_TOKEN') ?? '');
    }

    public static function getErrorLoggingAccessTokenExpiresAt(): int
    {
        return (int) (self::getConfigurationValue('COMFINO_ERROR_LOGGING_ACCESS_TOKEN_EXPIRES_AT') ?? 0);
    }

    public static function refreshErrorLoggingTokenIfNeeded(): void
    {
        if (empty(self::getApiKey())) {
            return;
        }

        if (self::getErrorLoggingAccessToken() !== '' && self::getErrorLoggingAccessTokenExpiresAt() > time() + 3600) {
            return;
        }

        try {
            $response = ApiClient::getInstance()->claimErrorLoggingToken();

            if ($response !== null) {
                self::getInstance()->setConfigurationValue('COMFINO_ERROR_LOGGING_ACCESS_TOKEN', $response->accessToken);
                self::getInstance()->setConfigurationValue('COMFINO_ERROR_LOGGING_ACCESS_TOKEN_EXPIRES_AT', strtotime($response->expiresAt));
                self::getInstance()->persist();
            }
        } catch (\Throwable $e) {
            // Silently ignore — CETS token claim is best-effort.
        }
    }

    /**
     * @return string[]
     */
    public static function getIgnoredStatuses(): array
    {
        $ignoredStatuses = self::getConfigurationValue('COMFINO_IGNORED_STATUSES');
        if (!is_array($ignoredStatuses)) {
            $ignoredStatuses = null;
        }

        return $ignoredStatuses ?? StatusManager::DEFAULT_IGNORED_STATUSES;
    }

    /**
     * @return string[]
     */
    public static function getForbiddenStatuses(): array
    {
        $forbiddenStatuses = self::getConfigurationValue('COMFINO_FORBIDDEN_STATUSES');
        if (!is_array($forbiddenStatuses)) {
            $forbiddenStatuses = null;
        }

        return $forbiddenStatuses ?? StatusManager::DEFAULT_FORBIDDEN_STATUSES;
    }

    /**
     * @return string[]
     */
    public static function getStatusMap(): array
    {
        if (!is_array($statusMap = self::getConfigurationValue('COMFINO_STATUS_MAP'))) {
            $statusMap = null;
        }

        return $statusMap ?? ShopStatusManager::DEFAULT_STATUS_MAP;
    }

    /**
     * Returns the Magento order status code to set when an order is submitted to Comfino.
     * Defaults to comfino_created; can be overridden by the shop owner via COMFINO_INITIAL_ORDER_STATUS.
     */
    public static function getInitialOrderStatus(): string
    {
        return (string) (
            self::getConfigurationValue('COMFINO_INITIAL_ORDER_STATUS')
                ?: ShopStatusManager::CUSTOM_STATUS_MAP[StatusManager::STATUS_CREATED]
        );
    }

    public static function getConfigurationValues(string $optionsGroup, array $optionsToReturn = []): array
    {
        if (!array_key_exists($optionsGroup, self::CONFIG_OPTIONS)) {
            return [];
        }

        return count($optionsToReturn)
            ? self::getInstance()->getConfigurationValues($optionsToReturn)
            : self::getInstance()->getConfigurationValues(array_keys(self::CONFIG_OPTIONS[$optionsGroup]));
    }

    public static function getWidgetVariables(?int $productId = null): array
    {
        /** @var Data $dataHelper */
        $dataHelper = ObjectManager::getInstance()->get(Data::class);
        $productData = self::getProductData($productId);

        try {
            $currency = ObjectManager::getInstance()
                ->get(\Magento\Store\Model\StoreManagerInterface::class)
                ->getStore()
                ->getCurrentCurrencyCode();
        } catch (\Throwable $e) {
            $currency = 'PLN';
        }

        return [
            'PRODUCT_ID' => $productData['product_id'],
            'PRODUCT_PRICE' => $productData['price'],
            'PLATFORM' => 'magento',
            'PLATFORM_NAME' => 'Magento',
            'PLATFORM_VERSION' => $dataHelper->getShopVersion(),
            'PLATFORM_DOMAIN' => $dataHelper->getShopDomain(),
            'PLUGIN_VERSION' => $dataHelper->getModuleVersion(),
            'AVAILABLE_PRODUCT_TYPES' => $productData['available_product_types'],
            'PRODUCT_CART_DETAILS' => $productData['product_cart_details'],
            'LANGUAGE' => $dataHelper->getShopLanguage(),
            'CURRENCY' => $currency,
            'LOGGING_TOKEN' => ObjectManager::getInstance()->get(PaywallAuthTokenGenerator::class)->generateLoggingToken(),
            'TRACK_ID' => ApiClient::getInstance()->getTrackId(),
        ];
    }

    public static function getDefaultConfigurationValues(): array
    {
        return [
            'COMFINO_PAYMENT_TEXT' => '(Raty | Kup Teraz, Zapłać Później | Finansowanie dla Firm)',
            'COMFINO_MINIMAL_CART_AMOUNT' => 30,
            'COMFINO_USE_ORDER_REFERENCE' => false,
            'COMFINO_IS_SANDBOX' => false,
            'COMFINO_DEBUG' => false,
            'COMFINO_SERVICE_MODE' => false,
            'COMFINO_DEV_ENV_VARS' => false,
            'COMFINO_PRODUCT_CATEGORY_FILTERS' => '',
            'COMFINO_CAT_FILTER_AVAIL_PROD_TYPES' => 'INSTALLMENTS_ZERO_PERCENT,PAY_LATER,COMPANY_BNPL,COMPANY_INSTALLMENTS,LEASING,PAY_IN_PARTS',
            'COMFINO_WIDGET_ENABLED' => false,
            'COMFINO_WIDGET_KEY' => '',
            'COMFINO_WIDGET_PRICE_SELECTOR' => '[data-price-type="finalPrice"]',
            'COMFINO_WIDGET_PRICE_ATTRIBUTE' => 'data-price-amount',
            'COMFINO_WIDGET_TARGET_SELECTOR' => 'div.product-add-form',
            'COMFINO_WIDGET_PRICE_OBSERVER_SELECTOR' => '',
            'COMFINO_WIDGET_PRICE_OBSERVER_LEVEL' => 0,
            'COMFINO_WIDGET_TYPE' => 'standard',
            'COMFINO_WIDGET_OFFER_TYPES' => 'CONVENIENT_INSTALLMENTS',
            'COMFINO_WIDGET_EMBED_METHOD' => 'INSERT_INTO_LAST',
            'COMFINO_WIDGET_SHOW_PROVIDER_LOGOS' => false,
            'COMFINO_WIDGET_CUSTOM_BANNER_CSS_URL' => '',
            'COMFINO_WIDGET_CUSTOM_CALCULATOR_CSS_URL' => '',
            'COMFINO_PROD_CAT_CACHE_TTL' => 3600,
            'COMFINO_INITIAL_ORDER_STATUS' => ShopStatusManager::CUSTOM_STATUS_MAP[StatusManager::STATUS_CREATED],
            'COMFINO_IGNORED_STATUSES' => implode(',', StatusManager::DEFAULT_IGNORED_STATUSES),
            'COMFINO_FORBIDDEN_STATUSES' => implode(',', StatusManager::DEFAULT_FORBIDDEN_STATUSES),
            'COMFINO_STATUS_MAP' => json_encode(ShopStatusManager::DEFAULT_STATUS_MAP),
            'COMFINO_API_CONNECT_TIMEOUT' => 3,
            'COMFINO_API_TIMEOUT' => 5,
            'COMFINO_API_CONNECT_NUM_ATTEMPTS' => 3,
        ];
    }

    private static function getProductData(?int $productId): array
    {
        $price = 'null';
        $productCartDetails = 'null';

        if ($productId !== null) {
            try {
                /** @var ProductRepositoryInterface $productRepository */
                $productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $productRepository->getById($productId);
                $price = $product->getFinalPrice();
                $shopCart = OrderManager::getShopCartFromProduct($product);
                $availableProductTypes = SettingsManager::getAllowedProductTypes(
                    ProductTypesListTypeEnum::LIST_TYPE_WIDGET,
                    $shopCart,
                    true
                );
                $productCartDetails = $shopCart->getAsArray();
            } catch (\Throwable $e) {
                // Product not found or error - fall back to unfiltered product types.
                $availableProductTypes = SettingsManager::getProductTypesStrings(
                    ProductTypesListTypeEnum::LIST_TYPE_WIDGET
                );
            }
        } else {
            $availableProductTypes = SettingsManager::getProductTypesStrings(
                ProductTypesListTypeEnum::LIST_TYPE_WIDGET
            );
        }

        return [
            'product_id' => $productId ?? 'null',
            'price' => $price,
            'available_product_types' => $availableProductTypes,
            'product_cart_details' => $productCartDetails,
        ];
    }

    private static function getAvailableConfigOptions(): array
    {
        if (self::$availConfigOptions === null) {
            self::$availConfigOptions = array_merge(array_merge(...array_values(self::CONFIG_OPTIONS)));
        }

        return self::$availConfigOptions;
    }
}
