<?php

declare(strict_types=1);

namespace Comfino\Api\Request;

use Comfino\Api\Dto\Plugin\ShopEnvironmentReport;
use Comfino\Api\Exception\RequestValidationError;
use Comfino\Api\Request;

class ReportShopEnvironment extends Request
{
    /**
     * @var \Comfino\Api\Dto\Plugin\ShopEnvironmentReport
     */
    private $report;
    private const MAX_STRING_FIELD_LENGTH = 256;
    private const MAX_THEME_PARENTS = 10;
    private const MAX_CAPABILITIES = 32;
    private const MAX_META_ENTRIES = 32;
    private const MAX_META_VALUE_LENGTH = 200;
    private const MAX_URL_LENGTH = 2048;

    public function __construct(ShopEnvironmentReport $report)
    {
        $this->report = $report;
        $this->setRequestMethod('POST');
        $this->setApiEndpointPath('log-shop-environment');
    }

    protected function prepareRequestBody(): ?array
    {
        $report = $this->report;

        if ($report->platform === '' || $report->platformDomain === '' || $report->pluginVersion === '') {
            throw new RequestValidationError(
                'ShopEnvironmentReport requires non-empty platform, platformDomain, and pluginVersion.'
            );
        }

        $theme = $report->theme;

        $themeFamily = $theme->family !== ''
            ? self::truncate($theme->family, self::MAX_STRING_FIELD_LENGTH)
            : 'custom';

        $themeParents = [];

        foreach ($theme->parents as $parent) {
            if (count($themeParents) >= self::MAX_THEME_PARENTS) {
                break;
            }

            if ($parent !== '') {
                $themeParents[] = self::truncate($parent, self::MAX_STRING_FIELD_LENGTH);
            }
        }

        $themePayload = [
            'code' => self::truncate($theme->code, self::MAX_STRING_FIELD_LENGTH),
            'family' => $themeFamily,
            'parents' => $themeParents,
        ];

        if ($theme->isPwa !== null) {
            $themePayload['is_pwa'] = $theme->isPwa;
        }

        return [
            'platform' => self::truncate($report->platform, self::MAX_STRING_FIELD_LENGTH),
            'platform_name' => self::truncate($report->platformName, self::MAX_STRING_FIELD_LENGTH),
            'platform_version' => self::truncate($report->platformVersion, self::MAX_STRING_FIELD_LENGTH),
            'platform_edition' => $report->platformEdition !== null
                ? self::truncate($report->platformEdition, self::MAX_STRING_FIELD_LENGTH)
                : null,
            'platform_domain' => self::truncate($report->platformDomain, self::MAX_STRING_FIELD_LENGTH),
            'plugin_version' => self::truncate($report->pluginVersion, self::MAX_STRING_FIELD_LENGTH),
            'theme' => $themePayload,
            'language' => self::truncate($report->language, self::MAX_STRING_FIELD_LENGTH),
            'currency' => self::truncate($report->currency, self::MAX_STRING_FIELD_LENGTH),
            'capabilities' => self::sanitizeCapabilities($report->capabilities),
            'test_product_url' => $report->testProductUrl !== null
                ? self::truncate($report->testProductUrl, self::MAX_URL_LENGTH)
                : null,
            'meta' => self::sanitizeMeta($report->meta),
        ];
    }

    private static function truncate(string $value, int $maxLength): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength - 3) . '...';
    }

    /**
     * @return array<string,
     */
    private static function sanitizeCapabilities(array $capabilities): array
    {
        $sanitized = [];

        foreach ($capabilities as $key => $value) {
            if (count($sanitized) >= self::MAX_CAPABILITIES) {
                break;
            }

            if (is_string($key) && $key !== '') {
                $sanitized[$key] = (bool) $value;
            }
        }

        return $sanitized;
    }

    /**
     * @return array<string,
     */
    private static function sanitizeMeta(array $meta): array
    {
        $sanitized = [];

        foreach ($meta as $key => $value) {
            if (count($sanitized) >= self::MAX_META_ENTRIES) {
                break;
            }

            if (!is_string($key) || $key === '') {
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value)) {
                $sanitized[$key] = $value;
            } elseif (is_string($value)) {
                $sanitized[$key] = self::truncate($value, self::MAX_META_VALUE_LENGTH);
            }
        }

        return $sanitized;
    }
}
