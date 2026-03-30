<?php

namespace Comfino\ComfinoGateway\Block\Widget;

use Comfino\Configuration\ConfigManager;
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

    public function getWidgetInitScriptUrl(): string
    {
        if (!ConfigManager::isWidgetEnabled() || ConfigManager::getWidgetKey() === '') {
            return '';
        }

        $product = $this->registry->registry('current_product');
        $productId = $product ? (int) $product->getId() : 0;

        return $this->getUrl('comfino/script/index', ['product_id' => $productId]);
    }
}