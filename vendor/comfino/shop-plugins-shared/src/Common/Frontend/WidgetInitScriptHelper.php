<?php

namespace Comfino\Common\Frontend;

class WidgetInitScriptHelper
{
    public const WIDGET_INIT_PARAMS = [
        'WIDGET_KEY',
        'WIDGET_PRICE_SELECTOR',
        'WIDGET_TARGET_SELECTOR',
        'WIDGET_PRICE_OBSERVER_SELECTOR',
        'WIDGET_PRICE_OBSERVER_LEVEL',
        'WIDGET_TYPE',
        'OFFER_TYPES',
        'EMBED_METHOD',
        'SHOW_PROVIDER_LOGOS',
        'CUSTOM_BANNER_CSS_URL',
        'CUSTOM_CALCULATOR_CSS_URL',
    ];

    public const WIDGET_INIT_VARIABLES = [
        'WIDGET_SCRIPT_URL',
        'PRODUCT_ID',
        'PRODUCT_PRICE',
        'PLATFORM',
        'PLATFORM_VERSION',
        'PLATFORM_DOMAIN',
        'PLUGIN_VERSION',
        'AVAILABLE_PRODUCT_TYPES',
        'PRODUCT_CART_DETAILS',
        'LANGUAGE',
        'CURRENCY',
    ];

    /**
     * @throws \InvalidArgumentException
     * @param string $widgetInitCode
     * @param mixed[] $widgetInitParams
     * @param mixed[] $widgetInitVariables
     */
    public static function renderWidgetInitScript($widgetInitCode, $widgetInitParams, $widgetInitVariables): string
    {
        $widgetInitParamsAssocKeys = array_flip(self::WIDGET_INIT_PARAMS);
        $widgetInitVariablesAssocKeys = array_flip(self::WIDGET_INIT_VARIABLES);

        if (count(array_intersect_key($widgetInitParamsAssocKeys, $widgetInitParams)) !== count(self::WIDGET_INIT_PARAMS)) {
            throw new \InvalidArgumentException('Invalid widget initialization parameters.');
        }

        if (count(array_intersect_key($widgetInitVariablesAssocKeys, $widgetInitVariables)) !== count(self::WIDGET_INIT_VARIABLES)) {
            throw new \InvalidArgumentException('Invalid widget initialization variables.');
        }

        return str_replace(
            array_merge(
                array_map(
                    static function (string $widgetInitParamName): string {
                        return '{' . $widgetInitParamName . '}';
                    },
                    array_merge(self::WIDGET_INIT_PARAMS, array_keys($widgetInitVariables))
                ),
                ["'true'", "'false'", "'null'"]
            ),
            array_merge(
                array_map(
                    static function ($varValue): string {
                        if (is_bool($varValue)) {
                            return $varValue ? 'true' : 'false';
                        }

                        if (is_array($varValue)) {
                            return ($result = json_encode($varValue)) !== false ? $result : '[]';
                        }

                        return $varValue !== null ? (string) $varValue : 'null';
                    },
                    array_merge(
                        array_merge($widgetInitParamsAssocKeys, $widgetInitParams),
                        array_values($widgetInitVariables)
                    )
                ),
                ['true', 'false', 'null']
            ),
            $widgetInitCode
        );
    }

    /**
     * @param string $widgetInitCode
     */
    public static function initScriptRequiresUpdate($widgetInitCode): bool
    {
        return md5($widgetInitCode) !== md5(self::getInitialWidgetCode());
    }

    public static function getInitialWidgetCodeHash(): string
    {
        return md5(self::getInitialWidgetCode());
    }

    public static function getInitialWidgetCode(): string
    {
        return include 'WidgetInitScript.php';
    }
}
