<?php

namespace Comfino\ComfinoGateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const XML_PATH_API_KEY = 'payment/comfino/api_key';
    public const XML_PATH_TAX_ID = 'payment/comfino/tax_id';
    public const XML_PATH_MINIMAL_CART_AMOUNT = 'payment/comfino/minimal_cart_amount';
    public const XML_PATH_WIDGET_ENABLED = 'payment/comfino/widget_enabled';
    public const XML_PATH_WIDGET_KEY = 'payment/comfino/widget_key';
    public const XML_PATH_WIDGET_PRICE_SELECTOR = 'payment/comfino/widget_price_selector';
    public const XML_PATH_WIDGET_TARGET_SELECTOR = 'payment/comfino/widget_target_selector';
    public const XML_PATH_WIDGET_TYPE = 'payment/comfino/widget_type';
    public const XML_PATH_WIDGET_OFFER_TYPE = 'payment/comfino/widget_offer_type';
    public const XML_PATH_WIDGET_EMBED_METHOD = 'payment/comfino/widget_embed_method';
    public const XML_PATH_WIDGET_CODE = 'payment/comfino/widget_code';
    public const XML_PATH_SANDBOX_ENABLED = 'payment/comfino/sandbox';

    private const MODULE_NAME = 'Comfino_ComfinoGateway';

    private const COMFINO_PRODUCTION_HOST = 'https://api-ecommerce.comfino.pl';
    private const COMFINO_SANDBOX_HOST = 'https://api-ecommerce.ecraty.pl';
    private const WIDGET_SCRIPT_PRODUCTION_URL = '//widget.comfino.pl/comfino.min.js';
    private const WIDGET_SCRIPT_SANDBOX_URL = '//widget.craty.pl/comfino.min.js';

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

    public function __construct(
        Context $context,
        SerializerInterface $serializer,
        ModuleListInterface $moduleList,
        ComponentRegistrarInterface $componentRegistrar,
        ReadFactory $readFactory
    )
    {
        $this->serializer = $serializer;
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;

        parent::__construct($context);
    }

    /**
     * Returns store configuration value by path.
     *
     * @param string $path
     * @return mixed
     */
    protected function getConfigValue(string $path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Is sandbox activated.
     *
     * @return bool
     */
    public function isSandboxEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SANDBOX_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Returns API key.
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_API_KEY);
    }

    /**
     * Returns tax ID.
     *
     * @return string|null
     */
    public function getTaxId(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_TAX_ID);
    }

    /**
     * Returns minimal cart amount for Comfino payments.
     *
     * @return float
     */
    public function getMinimalCartAmount(): float
    {
        return (float)$this->getConfigValue(self::XML_PATH_MINIMAL_CART_AMOUNT);
    }

    /**
     * Returns widget activation status.
     *
     * @return bool
     */
    public function getWidgetIsActive(): bool
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_ENABLED) === '1';
    }

    /**
     * Returns widget key.
     *
     * @return string|null
     */
    public function getWidgetKey(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_KEY);
    }

    /**
     * Returns widget price selector.
     *
     * @return string|null
     */
    public function getWidgetPriceSelector(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_PRICE_SELECTOR);
    }

    /**
     * Returns widget target selector.
     *
     * @return string|null
     */
    public function getWidgetTargetSelector(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_TARGET_SELECTOR);
    }

    /**
     * Returns widget type.
     *
     * @return string|null
     */
    public function getWidgetType(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_TYPE);
    }

    /**
     * Returns widget offer type.
     *
     * @return string|null
     */
    public function getWidgetOfferType(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_OFFER_TYPE);
    }

    /**
     * Returns widget embedding method.
     *
     * @return string|null
     */
    public function getWidgetEmbedMethod(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_EMBED_METHOD);
    }

    /**
     * Returns widget initialization code.
     *
     * @return string|null
     */
    public function getWidgetCode(): ?string
    {
        return $this->getConfigValue(self::XML_PATH_WIDGET_CODE);
    }

    /**
     * Returns production host.
     *
     * @return string|null
     */
    public function getProdUrl(): ?string
    {
        return self::COMFINO_PRODUCTION_HOST;
    }

    /**
     * Returns sandbox host.
     *
     * @return string|null
     */
    public function getSandboxUrl(): ?string
    {
        return self::COMFINO_SANDBOX_HOST;
    }

    /**
     * Returns production widget script URL.
     *
     * @return string|null
     */
    public function getProdWidgetScriptUrl(): ?string
    {
        return self::WIDGET_SCRIPT_PRODUCTION_URL;
    }

    /**
     * Returns production widget script URL.
     *
     * @return string|null
     */
    public function getSandboxWidgetScriptUrl(): ?string
    {
        return self::WIDGET_SCRIPT_SANDBOX_URL;
    }

    /**
     * Returns module setup version.
     *
     * @return string
     */
    public function getSetupVersion(): string
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * Returns module Composer version.
     *
     * @return Phrase|string|void
     */
    public function getModuleVersion()
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        $data = $this->serializer->unserialize($this->readFactory->create($path)->readFile('composer.json'));

        return $data['version'] ?? 'N/A';
    }
}
