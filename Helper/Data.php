<?php

namespace Comfino\ComfinoGateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
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

    private const MODULE_NAME = 'Comfino_ComfinoGateway';

    private const COMFINO_PRODUCTION_HOST = 'https://api-ecommerce.comfino.pl';
    private const COMFINO_SANDBOX_HOST = 'https://api-ecommerce.ecraty.pl';
    private const COMFINO_FRONTEND_JS_SANDBOX = 'https://widget.craty.pl/comfino-frontend.min.js';
    private const COMFINO_FRONTEND_JS_PRODUCTION = 'https://widget.comfino.pl/comfino-frontend.min.js';
    private const COMFINO_WIDGET_JS_SANDBOX = 'https://widget.craty.pl/comfino.min.js';
    private const COMFINO_WIDGET_JS_PRODUCTION = 'https://widget.comfino.pl/comfino.min.js';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var ComponentRegistrarInterface
     */
    private $componentRegistrar;

    /**
     * @var ReadFactory
     */
    private $readFactory;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetaData;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Resolver
     */
    private $localeResolver;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    public function __construct(
        Context $context,
        SerializerInterface $serializer,
        ModuleListInterface $moduleList,
        ComponentRegistrarInterface $componentRegistrar,
        ReadFactory $readFactory,
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager,
        Resolver $localeResolver,
        PriceHelper $priceHelper
    ) {
        $this->serializer = $serializer;
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;
        $this->productMetaData = $productMetadata;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
        $this->priceHelper = $priceHelper;

        parent::__construct($context);
    }

    /**
     * Returns store configuration value by path.
     */
    protected function getConfigValue(string $path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Is sandbox activated.
     */
    public function isSandboxEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SANDBOX_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Returns API key for production.
     *
     * @return string
     */
    public function getProductionApiKey(): string
    {
        return $this->getConfigValue(self::XML_PATH_API_KEY) ?? '';
    }

    /**
     * Returns API key for sandbox.
     */
    public function getSandboxApiKey(): string
    {
        return $this->getConfigValue(self::XML_PATH_SANDBOX_API_KEY) ?? '';
    }

    /**
     * Returns API URL depending on sandbox activation state.
     */
    public function getApiHost($frontendHost = false): string
    {
        if (getenv('COMFINO_DEV') && getenv('COMFINO_DEV') === 'MG_' . $this->getShopVersion() . '_' . $this->getShopUrl()) {
            if ($frontendHost) {
                if (getenv('COMFINO_DEV_API_HOST_FRONTEND')) {
                    return getenv('COMFINO_DEV_API_HOST_FRONTEND');
                }
            } else {
                if (getenv('COMFINO_DEV_API_HOST_BACKEND')) {
                    return getenv('COMFINO_DEV_API_HOST_BACKEND');
                }
            }
        }

        return $this->isSandboxEnabled() ? self::COMFINO_SANDBOX_HOST : self::COMFINO_PRODUCTION_HOST;
    }

    /**
     * Returns API key depending on sandbox activation state.
     */
    public function getApiKey(): string
    {
        return $this->isSandboxEnabled() ? $this->getSandboxApiKey() : $this->getProductionApiKey()();
    }

    /**
     * Returns frontend script URL.
     */
    public function getFrontendScriptUrl(): string
    {
        if (getenv('COMFINO_DEV') && getenv('COMFINO_DEV_FRONTEND_SCRIPT_URL') &&
            getenv('COMFINO_DEV') === 'MG_' . $this->getShopVersion() . '_' . $this->getShopUrl()
        ) {
            return getenv('COMFINO_DEV_FRONTEND_SCRIPT_URL');
        }

        return $this->isSandboxEnabled() ? self::COMFINO_FRONTEND_JS_SANDBOX : self::COMFINO_FRONTEND_JS_PRODUCTION;
    }

    /**
     * Returns widget script URL.
     */
    public function getWidgetScriptUrl(): ?string
    {
        if (getenv('COMFINO_DEV') && getenv('COMFINO_DEV_WIDGET_SCRIPT_URL') &&
            getenv('COMFINO_DEV') === 'MG_' . $this->getShopVersion() . '_' . $this->getShopUrl()
        ) {
            return getenv('COMFINO_DEV_WIDGET_SCRIPT_URL');
        }

        return $this->isSandboxEnabled() ? self::COMFINO_WIDGET_JS_SANDBOX : self::COMFINO_WIDGET_JS_PRODUCTION;
    }

    public function getOffersUrl(float $total): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . "rest/V1/comfino-gateway/offers?total=$total";
    }

    /**
     * Returns minimal cart amount for Comfino payments.
     */
    public function getMinimalCartAmount(): float
    {
        return (float) $this->getConfigValue(self::XML_PATH_MINIMAL_CART_AMOUNT);
    }

    /**
     * Returns widget activation status.
     */
    public function isWidgetActive(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_WIDGET_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Returns widget key.
     */
    public function getWidgetKey(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_KEY);
    }

    /**
     * Returns widget price selector.
     */
    public function getWidgetPriceSelector(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_PRICE_SELECTOR);
    }

    /**
     * Returns widget target selector.
     */
    public function getWidgetTargetSelector(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_TARGET_SELECTOR);
    }

    public function getPriceObserverSelector(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_PRICE_OBSERVER_SELECTOR);
    }

    public function getPriceObserverLevel(): int
    {
        return (int)$this->getConfigValue(self::XML_PATH_WIDGET_PRICE_OBSERVER_LEVEL);
    }

    /**
     * Returns widget type.
     */
    public function getWidgetType(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_TYPE);
    }

    /**
     * Returns widget offer type.
     */
    public function getWidgetOfferType(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_OFFER_TYPE);
    }

    /**
     * Returns widget embedding method.
     */
    public function getWidgetEmbedMethod(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_EMBED_METHOD);
    }

    /**
     * Returns widget initialization code.
     */
    public function getWidgetCode(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_CODE);
    }

    /**
     * Returns module setup version.
     */
    public function getSetupVersion(): string
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
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

    public function isValidSignature(string $crSignature, string $jsonData): bool
    {
        return hash_equals(hash('sha3-256', $this->getApiKey() . $jsonData), $crSignature);
    }

    public function formatPrice(float $price): string
    {
        return $this->priceHelper->currency($price, true, false);
    }
}
