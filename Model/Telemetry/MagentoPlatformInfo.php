<?php

/**
 * Comfino Payment Gateway for Magento 2
 *
 * @package Comfino\ComfinoGateway\Model\Telemetry
 * @author Artur Kozubski <akozubski@comperia.pl>
 * @copyright Copyright (c) 2026 Comfino by Comperia.pl S.A.
 * @license https://opensource.org/licenses/OSL-3.0 Open Software License 3.0
 * @link https://github.com/comfino/magento2
 */

declare(strict_types=1);

namespace Comfino\ComfinoGateway\Model\Telemetry;

use Comfino\ComfinoGateway\Helper\Data;
use Comfino\Platform\PlatformInfoInterface;
use Magento\Store\Model\StoreManagerInterface;
use Throwable;

/**
 * Magento 2 implementation of the shared PlatformInfoInterface.
 *
 * Reads platform/shop metadata from the existing Comfino Helper\Data plus Magento's StoreManager (for the currency),
 * so the shop-environment builder can assemble a backend report.
 */
class MagentoPlatformInfo implements PlatformInfoInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(Data $helper, StoreManagerInterface $storeManager)
    {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    public function getCode(): string
    {
        return 'MG';
    }

    public function getName(): string
    {
        return 'Magento';
    }

    public function getVersion(): string
    {
        return $this->helper->getShopVersion();
    }

    public function getLanguage(): string
    {
        return $this->helper->getShopLanguage();
    }

    public function getCurrency(): string
    {
        try {
            return (string) $this->storeManager->getStore()->getCurrentCurrencyCode();
        } catch (Throwable $e) {
            return '';
        }
    }

    public function getDomain(): string
    {
        return (string) $this->helper->getShopDomain();
    }

    public function getDatabaseVersion(): string
    {
        return $this->helper->getDatabaseVersion();
    }

    public function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    public function getPluginVersion(): string
    {
        return $this->helper->getModuleVersion();
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'shopName' => $this->getName(),
            'shopVersion' => $this->getVersion(),
            'shopLanguage' => $this->getLanguage(),
            'shopCurrency' => $this->getCurrency(),
            'shopDomain' => $this->getDomain(),
            'databaseVersion' => $this->getDatabaseVersion(),
            'phpVersion' => $this->getPhpVersion(),
            'pluginVersion' => $this->getPluginVersion(),
        ];
    }
}