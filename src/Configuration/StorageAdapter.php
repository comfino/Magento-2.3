<?php

namespace Comfino\Configuration;

use Comfino\Common\Backend\Configuration\StorageAdapterInterface;
use Comfino\Common\Backend\ConfigurationManager;
use Comfino\ComfinoGateway\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Magento-specific storage adapter for Comfino ConfigurationManager.
 * Bridges Magento ScopeConfig <-> shared library ConfigurationManager.
 *
 * @see StorageAdapterInterface
 * @see ConfigurationManager
 */
class StorageAdapter implements StorageAdapterInterface
{
    private ScopeConfigInterface $scopeConfig;
    private WriterInterface $configWriter;
    /** @var int[] */
    private array $optTypeFlags;

    /** Maps COMFINO_* key names -> Magento XML config paths */
    private static array $keyToXmlPath = [
        'COMFINO_API_KEY' => Data::XML_PATH_API_KEY,
        'COMFINO_SANDBOX_API_KEY' => Data::XML_PATH_SANDBOX_API_KEY,
        'COMFINO_IS_SANDBOX' => Data::XML_PATH_SANDBOX_ENABLED,
        'COMFINO_PAYMENT_TEXT' => Data::XML_PATH_PAYWALL_TITLE,
        'COMFINO_MINIMAL_CART_AMOUNT' => Data::XML_PATH_MINIMAL_CART_AMOUNT,
        'COMFINO_USE_ORDER_REFERENCE' => Data::XML_PATH_USE_ORDER_REFERENCE,
        'COMFINO_PRODUCT_CATEGORY_FILTERS' => Data::XML_PATH_PRODUCT_CATEGORY_FILTERS,
        'COMFINO_DEBUG' => Data::XML_PATH_DEBUG,
        'COMFINO_SERVICE_MODE' => Data::XML_PATH_SERVICE_MODE,
        'COMFINO_DEV_ENV_VARS' => Data::XML_PATH_DEV_ENV_VARS,
        'COMFINO_WIDGET_ENABLED' => Data::XML_PATH_WIDGET_ENABLED,
        'COMFINO_WIDGET_KEY' => Data::XML_PATH_WIDGET_KEY,
        'COMFINO_WIDGET_PRICE_SELECTOR' => Data::XML_PATH_WIDGET_PRICE_SELECTOR,
        'COMFINO_WIDGET_TARGET_SELECTOR' => Data::XML_PATH_WIDGET_TARGET_SELECTOR,
        'COMFINO_WIDGET_PRICE_OBSERVER_SELECTOR' => Data::XML_PATH_WIDGET_PRICE_OBSERVER_SELECTOR,
        'COMFINO_WIDGET_PRICE_OBSERVER_LEVEL' => Data::XML_PATH_WIDGET_PRICE_OBSERVER_LEVEL,
        'COMFINO_WIDGET_TYPE' => Data::XML_PATH_WIDGET_TYPE,
        'COMFINO_WIDGET_OFFER_TYPES' => Data::XML_PATH_WIDGET_OFFER_TYPE,
        'COMFINO_WIDGET_EMBED_METHOD' => Data::XML_PATH_WIDGET_EMBED_METHOD,
        'COMFINO_WIDGET_SHOW_PROVIDER_LOGOS' => Data::XML_PATH_WIDGET_SHOW_PROVIDER_LOGOS,
        'COMFINO_WIDGET_CUSTOM_BANNER_CSS_URL' => Data::XML_PATH_WIDGET_CUSTOM_BANNER_CSS_URL,
        'COMFINO_WIDGET_CUSTOM_CALCULATOR_CSS_URL' => Data::XML_PATH_WIDGET_CUSTOM_CALCULATOR_CSS_URL,
        'COMFINO_WIDGET_CODE' => Data::XML_PATH_WIDGET_CODE,
        'COMFINO_WIDGET_PROD_SCRIPT_VERSION' => Data::XML_PATH_WIDGET_PROD_SCRIPT_VERSION,
        'COMFINO_WIDGET_DEV_SCRIPT_VERSION' => Data::XML_PATH_WIDGET_DEV_SCRIPT_VERSION,
        'COMFINO_CAT_FILTER_AVAIL_PROD_TYPES' => Data::XML_PATH_CAT_FILTER_AVAIL_PROD_TYPES,
        'COMFINO_PROD_CAT_CACHE_TTL' => Data::XML_PATH_PROD_CAT_CACHE_TTL,
        'COMFINO_INITIAL_ORDER_STATUS' => Data::XML_PATH_INITIAL_ORDER_STATUS,
        'COMFINO_IGNORED_STATUSES' => Data::XML_PATH_IGNORED_STATUSES,
        'COMFINO_FORBIDDEN_STATUSES' => Data::XML_PATH_FORBIDDEN_STATUSES,
        'COMFINO_STATUS_MAP' => Data::XML_PATH_STATUS_MAP,
        'COMFINO_API_CONNECT_TIMEOUT' => Data::XML_PATH_API_CONNECT_TIMEOUT,
        'COMFINO_API_TIMEOUT' => Data::XML_PATH_API_TIMEOUT,
        'COMFINO_API_CONNECT_NUM_ATTEMPTS' => Data::XML_PATH_API_CONNECT_NUM_ATTEMPTS,
    ];

    public function __construct(ScopeConfigInterface $scopeConfig, WriterInterface $configWriter)
    {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->optTypeFlags = array_merge(array_merge(...array_values(ConfigManager::CONFIG_OPTIONS)));
    }

    /**
     * Loads all configuration values from Magento store config.
     */
    public function load(): array
    {
        $configuration = [];
        $defaults = ConfigManager::getDefaultConfigurationValues();

        foreach ($this->optTypeFlags as $optName => $optTypeFlags) {
            $xmlPath = self::$keyToXmlPath[$optName] ?? null;

            if ($xmlPath !== null) {
                $value = $this->scopeConfig->getValue($xmlPath, ScopeInterface::SCOPE_STORE);
                $configuration[$optName] = $value ?? ($defaults[$optName] ?? null);
            } else {
                $configuration[$optName] = $defaults[$optName] ?? null;
            }

            if ($optTypeFlags & ConfigurationManager::OPT_VALUE_TYPE_BOOL) {
                $configuration[$optName] = (bool) $configuration[$optName];
            }
        }

        return $configuration;
    }

    /**
     * Saves configuration values to Magento store config.
     */
    public function save($configurationOptions): void
    {
        foreach ($configurationOptions as $optName => $optValue) {
            $xmlPath = self::$keyToXmlPath[$optName] ?? null;

            if ($xmlPath !== null) {
                $this->configWriter->save($xmlPath, $optValue);
            }
        }
    }
}
