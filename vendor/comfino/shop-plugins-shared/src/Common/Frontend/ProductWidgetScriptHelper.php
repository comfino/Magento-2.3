<?php

declare(strict_types=1);

namespace Comfino\Common\Frontend;

class ProductWidgetScriptHelper
{
    public const SCRIPT_PRODUCTION_HOST = 'https://sdk.comfino.pl';
    public const SCRIPT_SANDBOX_HOST = 'https://sdk.craty.pl';
    public const DEFAULT_SCRIPT_VERSION = 1;

    public const CONFIG_ELEMENT_ID = 'comfino-widget-config';

    public const WIDGET_CONFIG_KEYS = [
        'sdkScriptUrl',
        'environment',
        'widgetKey',
        'loggingToken',
        'trackId',
        'components',
        'container',
        'widgetTargetSelector',
        'priceSelector',
        'priceSelectors',
        'priceCombine',
        'priceAttribute',
        'priceObserverSelector',
        'priceObserverLevel',
        'quantitySelector',
        'triggerSelector',
        'embedMethod',
        'price',
        'priceType',
        'vatRate',
        'bannerCssUrl',
        'calculatorCssUrl',
        'widgetType',
        'offerTypes',
        'availableProductTypes',
        'hasPriceInput',
        'showProviderLogos',
        'language',
        'currency',
        'productId',
        'productCartDetails',
        'shopEnvironment',
    ];

    /**
     * @param bool $sandboxMode
     * @param string|null $platform
     * @param int $version
     * @return string
     */
    public static function getScriptUrl(
        $sandboxMode,
        $platform = null,
        $version = self::DEFAULT_SCRIPT_VERSION
    ): string {
        $host = $sandboxMode ? self::SCRIPT_SANDBOX_HOST : self::SCRIPT_PRODUCTION_HOST;
        $normalizedPlatform = $platform !== null ? strtolower(trim($platform)) : '';

        $file = ($normalizedPlatform === '' || $normalizedPlatform === 'generic')
            ? 'comfino-widget.min.js'
            : 'comfino-' . $normalizedPlatform . '-widget.min.js';

        return $host . '/product/v' . $version . '/' . $file;
    }

    /**
     * @return array<string,
     */
    public static function buildConfig($values): array
    {
        $allowed = array_flip(self::WIDGET_CONFIG_KEYS);

        return array_filter(
            $values,
            static function ($value, $key) use ($allowed) {
                return isset($allowed[$key]) && $value !== null;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * @param string $scriptUrl
     * @return string
     */
    public static function renderScript($config, $scriptUrl): string
    {
        $json = json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $json = '{}';
        }

        return '<script type="application/json" id="' . self::CONFIG_ELEMENT_ID . '">' . $json . '</script>' .
            '<script src="' . htmlspecialchars($scriptUrl, ENT_QUOTES) . '"></script>';
    }
}
