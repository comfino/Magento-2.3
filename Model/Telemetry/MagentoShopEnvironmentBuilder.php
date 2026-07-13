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
use Magento\Framework\View\DesignInterface;
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

    public function __construct(PlatformInfoInterface $platformInfo, ThemeFamilyRules $rules, DesignInterface $design)
    {
        parent::__construct($platformInfo, $rules);

        $this->design = $design;
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
     * Reads the active design theme from Magento's DesignInterface, walks the parent chain, and resolves the normalized
     * family via ThemeFamilyRules.
     */
    protected function detectTheme(): ShopTheme
    {
        try {
            $theme = $this->design->getDesignTheme();
            $code = (string) $theme->getCode();
            $parents = $theme instanceof Theme ? $this->collectThemeParents($theme) : [];
        } catch (Throwable $e) {
            return new ShopTheme('', 'custom', []);
        }

        $family = $this->rules->resolveFamily(array_map('strtolower', array_merge([$code], $parents)));

        return new ShopTheme($code, $family, $parents);
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