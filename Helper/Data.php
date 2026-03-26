<?php

namespace Comfino\ComfinoGateway\Helper;

use Comfino\Configuration\ConfigManager;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\DB\Adapter\SqlVersionProvider;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_API_KEY = 'payment/comfino/api_key';
    public const XML_PATH_MINIMAL_CART_AMOUNT = 'payment/comfino/minimal_cart_amount';
    public const XML_PATH_WIDGET_ENABLED = 'payment/comfino/widget_enabled';
    public const XML_PATH_WIDGET_KEY = 'payment/comfino/widget_key';
    public const XML_PATH_WIDGET_PRICE_SELECTOR = 'payment/comfino/widget_price_selector';
    public const XML_PATH_WIDGET_TARGET_SELECTOR = 'payment/comfino/widget_target_selector';
    public const XML_PATH_WIDGET_PRICE_OBSERVER_SELECTOR = 'payment/comfino/widget_price_observer_selector';
    public const XML_PATH_WIDGET_PRICE_OBSERVER_LEVEL = 'payment/comfino/widget_price_observer_level';
    public const XML_PATH_WIDGET_TYPE = 'payment/comfino/widget_type';
    public const XML_PATH_WIDGET_OFFER_TYPE = 'payment/comfino/widget_offer_type';
    public const XML_PATH_WIDGET_EMBED_METHOD = 'payment/comfino/widget_embed_method';
    public const XML_PATH_WIDGET_CODE = 'payment/comfino/widget_code';
    public const XML_PATH_SANDBOX_ENABLED = 'payment/comfino/sandbox';
    public const XML_PATH_SANDBOX_API_KEY = 'payment/comfino/sandbox_api_key';
    public const XML_PATH_PAYWALL_TITLE = 'payment/comfino/paywall_title';
    public const XML_PATH_USE_ORDER_REFERENCE = 'payment/comfino/use_order_reference';
    public const XML_PATH_PRODUCT_CATEGORY_FILTERS = 'payment/comfino/product_category_filters';
    public const XML_PATH_WIDGET_SHOW_PROVIDER_LOGOS = 'payment/comfino/widget_show_provider_logos';
    public const XML_PATH_WIDGET_CUSTOM_BANNER_CSS_URL = 'payment/comfino/widget_custom_banner_css_url';
    public const XML_PATH_WIDGET_CUSTOM_CALCULATOR_CSS_URL = 'payment/comfino/widget_custom_calculator_css_url';
    public const XML_PATH_DEBUG = 'payment/comfino/debug';
    public const XML_PATH_SERVICE_MODE = 'payment/comfino/service_mode';
    public const XML_PATH_DEV_ENV_VARS = 'payment/comfino/dev_env_vars';
    public const XML_PATH_IGNORED_STATUSES = 'payment/comfino/ignored_statuses';
    public const XML_PATH_FORBIDDEN_STATUSES = 'payment/comfino/forbidden_statuses';
    public const XML_PATH_STATUS_MAP = 'payment/comfino/status_map';
    public const XML_PATH_API_CONNECT_TIMEOUT = 'payment/comfino/api_connect_timeout';
    public const XML_PATH_API_TIMEOUT = 'payment/comfino/api_timeout';
    public const XML_PATH_API_CONNECT_NUM_ATTEMPTS = 'payment/comfino/api_connect_num_attempts';
    public const XML_PATH_WIDGET_PROD_SCRIPT_VERSION = 'payment/comfino/widget_prod_script_version';
    public const XML_PATH_WIDGET_DEV_SCRIPT_VERSION = 'payment/comfino/widget_dev_script_version';
    public const XML_PATH_CAT_FILTER_AVAIL_PROD_TYPES = 'payment/comfino/cat_filter_avail_prod_types';
    public const XML_PATH_PROD_CAT_CACHE_TTL = 'payment/comfino/prod_cat_cache_ttl';
    public const XML_PATH_INITIAL_ORDER_STATUS = 'payment/comfino/initial_order_status';

    public const BUILD_TS = 1774437957;

    private const MODULE_NAME = 'Comfino_ComfinoGateway';

    private SerializerInterface $serializer;
    private ComponentRegistrarInterface $componentRegistrar;
    private ReadFactory $readFactory;
    private ProductMetadataInterface $productMetaData;
    private StoreManagerInterface $storeManager;
    private Resolver $localeResolver;
    private SqlVersionProvider $sqlVersionProvider;

    public function __construct(
        Context $context,
        SerializerInterface $serializer,
        ComponentRegistrarInterface $componentRegistrar,
        ReadFactory $readFactory,
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager,
        Resolver $localeResolver,
        SqlVersionProvider $sqlVersionProvider
    ) {
        $this->serializer = $serializer;
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;
        $this->productMetaData = $productMetadata;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
        $this->sqlVersionProvider = $sqlVersionProvider;

        parent::__construct($context);
    }

    /**
     * Returns module Composer version.
     */
    public function getModuleVersion(): string
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        $data = $this->serializer->unserialize($this->readFactory->create($path)->readFile('composer.json'));

        return $data['version'] ?? 'N/A';
    }

    /**
     * Returns shop platform version.
     */
    public function getShopVersion(): string
    {
        return $this->productMetaData->getVersion();
    }

    /**
     * Returns DBMS engine version.
     */
    public function getDatabaseVersion(): string
    {
        $dbVersion = 'n/a';

        try {
            $dbVersion = $this->sqlVersionProvider->getSqlVersion();
        } catch (\Exception $e) {
            $matches = [];

            if (preg_match('/Used Version: (.+)\. Supported versions:/', $e->getMessage(), $matches)) {
                $dbVersion = $matches[1];
            }
        }

        return $dbVersion;
    }

    public function getShopUrl(): string
    {
        $urlParts = parse_url($this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB));

        return $urlParts['host'] . (isset($urlParts['port']) ? ':' . $urlParts['port'] : '');
    }

    public function getShopDomain(): string
    {
        return parse_url($this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB), PHP_URL_HOST);
    }

    public function getShopLanguage(): string
    {
        return substr($this->localeResolver->getLocale(), 0, 2);
    }

    /**
     * Returns widget JS URL (for product page widget). Delegates to ConfigManager.
     * Called from view/frontend/templates/widget/init.phtml.
     */
    public function getWidgetFrontendScriptUrl(): string
    {
        return ConfigManager::getWidgetScriptUrl();
    }
}