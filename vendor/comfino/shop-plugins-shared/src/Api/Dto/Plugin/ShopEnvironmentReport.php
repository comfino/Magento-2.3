<?php

declare(strict_types=1);

namespace Comfino\Api\Dto\Plugin;

final class ShopEnvironmentReport
{
    /**
     * @var string
     */
    public $platform;
    /**
     * @var string
     */
    public $platformName;
    /**
     * @var string
     */
    public $platformVersion;
    /**
     * @var string|null
     */
    public $platformEdition;
    /**
     * @var string
     */
    public $platformDomain;
    /**
     * @var string
     */
    public $pluginVersion;
    /**
     * @var ShopTheme
     */
    public $theme;
    /**
     * @var string
     */
    public $language;
    /**
     * @var string
     */
    public $currency;
    /**
     * @var array<string,
     */
    public $capabilities = [];
    /**
     * @var string|null
     */
    public $testProductUrl;
    /**
     * @var array<string,
     */
    public $meta = [];
    /**
     * @param string $platform
     * @param string $platformName
     * @param string $platformVersion
     * @param string|null $platformEdition
     * @param string $platformDomain
     * @param string $pluginVersion
     * @param ShopTheme $theme
     * @param string $language
     * @param string $currency
     * @param string|null $testProductUrl
     */
    public function __construct(string $platform, string $platformName, string $platformVersion, ?string $platformEdition, string $platformDomain, string $pluginVersion, ShopTheme $theme, string $language, string $currency, array $capabilities = [], ?string $testProductUrl = null, array $meta = [])
    {
        $this->platform = $platform;
        $this->platformName = $platformName;
        $this->platformVersion = $platformVersion;
        $this->platformEdition = $platformEdition;
        $this->platformDomain = $platformDomain;
        $this->pluginVersion = $pluginVersion;
        $this->theme = $theme;
        $this->language = $language;
        $this->currency = $currency;
        $this->capabilities = $capabilities;
        $this->testProductUrl = $testProductUrl;
        $this->meta = $meta;
    }
}
