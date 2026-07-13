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

use Comfino\Api\Dto\Plugin\ShopTheme;
use Comfino\Frontend\AbstractShopEnvironmentBuilder;
use Comfino\Frontend\ThemeFamilyRules;
use Comfino\Platform\PlatformInfoInterface;
use Magento\Framework\App\Area;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\Theme;
use Throwable;

/**
 * Magento 2 implementation of AbstractShopEnvironmentBuilder for the legacy (PHP 7.4) plugin distribution.
 *
 * Mirrors the magento2-dev MagentoShopEnvironmentBuilder: detects the active frontend theme and edition, and resolves
 * the theme family via ThemeFamilyRules (registered in the constructor — hyva/luma/blank). The parent class assembles
 * the structured report / frontend payload.
 */
class MagentoShopEnvironmentBuilder extends AbstractShopEnvironmentBuilder
{
    /**
     * @var DesignInterface
     */
    private $design;

    /**
     * @var ThemeProviderInterface
     */
    private $themeProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        PlatformInfoInterface $platformInfo,
        ThemeFamilyRules $rules,
        DesignInterface $design,
        ThemeProviderInterface $themeProvider,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($platformInfo, $rules);

        $this->design = $design;
        $this->themeProvider = $themeProvider;
        $this->storeManager = $storeManager;

        $this->registerThemeRules();
    }

    /**
     * {@inheritDoc}
     */
    protected function getPlatformIdentifier(): string
    {
        return 'magento';
    }

    /**
     * {@inheritDoc}
     */
    protected function getPlatformName(): string
    {
        return 'Magento';
    }

    /**
     * {@inheritDoc}
     *
     * Probes for Magento Commerce (Enterprise Edition) class markers. Returns 'enterprise' or 'community'.
     */
    protected function detectEdition(): ?string
    {
        if (
            class_exists('\\Magento\\CommerceBackendUi\\Setup\\InstallData') ||
            class_exists('\\Magento\\AdminGws\\Model\\Role')
        ) {
            return 'enterprise';
        }

        return 'community';
    }

    /**
     * {@inheritDoc}
     *
     * Resolves the storefront (frontend) theme, walks the parent chain, and resolves the normalized family via
     * ThemeFamilyRules.
     *
     * The backend report is triggered from the admin area (config save), where DesignInterface::getDesignTheme() would
     * return the adminhtml theme (Magento/backend) instead of the storefront theme. To report the theme the customer
     * actually sees, we read the configured frontend theme for the current store scope regardless of the active area.
     */
    protected function detectTheme(): ShopTheme
    {
        try {
            $theme = $this->resolveFrontendTheme();
            $code = $theme !== null ? (string) $theme->getCode() : '';
            $parents = $theme instanceof Theme ? $this->collectThemeParents($theme) : [];
        } catch (Throwable $e) {
            return new ShopTheme('', 'custom', []);
        }

        if ($code === '') {
            return new ShopTheme('', 'custom', []);
        }

        $family = $this->rules->resolveFamily(array_map('strtolower', array_merge([$code], $parents)));

        return new ShopTheme($code, $family, $parents);
    }

    /**
     * Resolves the theme configured for the storefront (frontend area) in the current store scope.
     *
     * DesignInterface::getConfigurationDesignTheme() returns either a numeric theme id (when a theme is selected in the
     * admin) or a theme path such as "Magento/luma" (when falling back to the default). Both forms are resolved to a
     * loaded Theme model here.
     *
     * @return ThemeInterface|null Loaded frontend theme, or null when it cannot be resolved.
     */
    private function resolveFrontendTheme(): ?ThemeInterface
    {
        $storeId = $this->storeManager->getStore()->getId();
        $configuredTheme = $this->design->getConfigurationDesignTheme(
            Area::AREA_FRONTEND,
            ['store' => $storeId]
        );

        if ($configuredTheme === null || $configuredTheme === '') {
            return null;
        }

        if (is_numeric($configuredTheme)) {
            return $this->themeProvider->getThemeById((int) $configuredTheme);
        }

        return $this->themeProvider->getThemeByFullPath(Area::AREA_FRONTEND . '/' . $configuredTheme);
    }

    /**
     * Walks the theme's parent chain and returns an ordered list of parent theme codes.
     *
     * @param Theme $theme Active theme model
     *
     * @return string[] Parent theme codes, child -> root order
     */
    private function collectThemeParents(Theme $theme): array
    {
        $parents = [];

        try {
            $parent = $theme->getParentTheme();
        } catch (Throwable $e) {
            return $parents;
        }

        $guard = 0;

        while ($parent instanceof Theme && $guard++ < 10) {
            $parents[] = (string) $parent->getCode();

            try {
                $parent = $parent->getParentTheme();
            } catch (Throwable $e) {
                break;
            }
        }

        return $parents;
    }

    /**
     * Registers the Magento theme-family detection predicates.
     */
    private function registerThemeRules(): void
    {
        $this->rules->register('hyva', static function (array $chain): bool {
            return self::chainContains($chain, 'hyva');
        });
        $this->rules->register('luma', static function (array $chain): bool {
            return self::chainContains($chain, 'luma');
        });
        $this->rules->register('blank', static function (array $chain): bool {
            return self::chainContains($chain, 'blank');
        });
    }

    /**
     * @param string[] $chain
     */
    private static function chainContains(array $chain, string $needle): bool
    {
        foreach ($chain as $theme) {
            if (strpos($theme, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}