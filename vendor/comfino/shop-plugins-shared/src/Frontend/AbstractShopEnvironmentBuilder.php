<?php

/**
 * Extended Comfino API client
 *
 * Ported from comfino/php-sdk (PHP 8.1+) to the PHP 7.1-compatible plugin distribution.
 *
 * @package Comfino\Frontend
 * @author Artur Kozubski <akozubski@comperia.pl>
 * @copyright Copyright (c) 2026 Comfino by Comperia.pl S.A.
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/comfino/extended-api-client
 */

declare(strict_types=1);

namespace Comfino\Frontend;

use Comfino\Api\Dto\Plugin\ShopEnvironmentReport;
use Comfino\Api\Dto\Plugin\ShopTheme;
use Comfino\Platform\PlatformInfoInterface;

abstract class AbstractShopEnvironmentBuilder
{
    /**
     * @var \Comfino\Platform\PlatformInfoInterface
     */
    protected $platformInfo;
    /**
     * @var \Comfino\Frontend\ThemeFamilyRules
     */
    protected $rules;
    public function __construct(PlatformInfoInterface $platformInfo, ThemeFamilyRules $rules)
    {
        $this->platformInfo = $platformInfo;
        $this->rules = $rules;
    }

    /**
     * @return array<string,
     */
    public function buildForFrontend($pageContext = null): array
    {
        $env = [
            'platform' => $this->getPlatformIdentifier(),
            'platformName' => $this->getPlatformName(),
            'platformDomain' => $this->platformInfo->getDomain(),
            'theme' => ['family' => $this->detectTheme()->family],
            'language' => $this->platformInfo->getLanguage(),
            'currency' => $this->platformInfo->getCurrency(),
        ];

        if ($pageContext !== null) {
            $env['pageContext'] = $pageContext;
        }

        return $env;
    }

    /**
     * @param string|null $testProductUrl
     */
    public function buildForBackendReport($testProductUrl = null, $meta = []): ShopEnvironmentReport
    {
        $theme = $this->detectTheme();

        return new ShopEnvironmentReport(
            $this->getPlatformIdentifier(),
            $this->getPlatformName(),
            $this->platformInfo->getVersion(),
            $this->detectEdition(),
            $this->platformInfo->getDomain(),
            $this->platformInfo->getPluginVersion(),
            $theme,
            $this->platformInfo->getLanguage(),
            $this->platformInfo->getCurrency(),
            CapabilityResolver::fromThemeFamily($theme->family),
            $testProductUrl,
            $meta
        );
    }

    /**
     * @param string|null $testProductUrl
     * @return array<string,
     */
    public function buildReportArray($testProductUrl = null, $meta = []): array
    {
        $report = $this->buildForBackendReport($testProductUrl, $meta);

        return [
            'platform' => $report->platform,
            'platform_name' => $report->platformName,
            'platform_version' => $report->platformVersion,
            'platform_edition' => $report->platformEdition,
            'platform_domain' => $report->platformDomain,
            'plugin_version' => $report->pluginVersion,
            'theme' => [
                'code' => $report->theme->code,
                'family' => $report->theme->family,
                'parents' => $report->theme->parents,
                'is_pwa' => $report->theme->isPwa,
            ],
            'language' => $report->language,
            'currency' => $report->currency,
            'capabilities' => $report->capabilities,
            'test_product_url' => $report->testProductUrl,
            'meta' => $report->meta,
        ];
    }

    abstract protected function getPlatformIdentifier(): string;

    abstract protected function getPlatformName(): string;

    abstract protected function detectTheme(): ShopTheme;

    /**
     * @return string|null
     */
    abstract protected function detectEdition(): ?string;
}
