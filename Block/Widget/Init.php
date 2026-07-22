<?php

namespace Comfino\ComfinoGateway\Block\Widget;

use Comfino\Common\Frontend\ProductWidgetScriptHelper;
use Comfino\Configuration\ConfigManager;
use Comfino\Configuration\SettingsManager;
use Comfino\FinancialProduct\ProductTypesListTypeEnum;
use Comfino\Order\OrderManager;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Init extends Template
{
    private Registry $registry;

    public function __construct(Context $context, Registry $registry, array $data = [])
    {
        parent::__construct($context, $data);

        $this->registry = $registry;
    }

    /**
     * Builds the JSON config for the `#comfino-widget-config` block consumed by the CDN product widget script. Returns
     * an empty string — suppressing the widget — when it is disabled, the widget key is missing, or all product types
     * are filtered out for the viewed product.
     */
    public function getWidgetConfigJson(): string
    {
        if (!ConfigManager::isWidgetEnabled() || ConfigManager::getWidgetKey() === '') {
            return '';
        }

        $product = $this->registry->registry('current_product');
        $productId = $product ? (int) $product->getId() : 0;

        if ($product !== null) {
            try {
                $allowedProductTypes = SettingsManager::getAllowedProductTypes(
                    ProductTypesListTypeEnum::LIST_TYPE_WIDGET,
                    OrderManager::getShopCartFromProduct($product)
                );

                if ($allowedProductTypes === []) {
                    // All product types filtered out for this product.
                    return '';
                }
            } catch (\Throwable $e) {
                // Ignore filter errors - show widget.
            }
        }

        $json = json_encode(
            ConfigManager::getWidgetConfig($productId ?: null),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
        );

        return $json === false ? '' : $json;
    }

    /**
     * URL of the CDN-hosted per-platform product widget script (comfino-magento-widget.min.js) that reads the
     * config block, imports the SDK, and calls sdk.bootstrapWidget().
     */
    public function getProductWidgetScriptUrl(): string
    {
        return ConfigManager::getProductWidgetScriptUrl();
    }

    /**
     * Element id of the `<script type="application/json">` config block, shared with the CDN widget script's
     * own reader via `ProductWidgetScriptHelper::CONFIG_ELEMENT_ID`.
     */
    public function getWidgetConfigElementId(): string
    {
        return ProductWidgetScriptHelper::CONFIG_ELEMENT_ID;
    }
}
