<?php

namespace Comfino\ComfinoGateway\Controller\Script;

use Comfino\Common\Frontend\WidgetInitScriptHelper;
use Comfino\Configuration\ConfigManager;
use Comfino\ErrorLogger;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;

class Index implements HttpGetActionInterface
{
    private RawFactory $resultRawFactory;
    private RequestInterface $request;

    public function __construct(RawFactory $resultRawFactory, RequestInterface $request)
    {
        $this->resultRawFactory = $resultRawFactory;
        $this->request = $request;
    }

    public function execute(): Raw
    {
        ErrorLogger::init();

        $result = $this->resultRawFactory->create();
        $result->setHeader('Content-Type', 'application/javascript');
        $result->setContents($this->buildScript());

        return $result;
    }

    private function buildScript(): string
    {
        if (!ConfigManager::isWidgetEnabled() || (string) ConfigManager::getWidgetKey() === '' ||
            (string) ConfigManager::getApiKey() === ''
        ) {
            return '';
        }

        /* Guard against stale `core_config_data` rows (e.g. a legacy `widget_type='error'` value written by a failed
           API sync) that would otherwise produce a broken script silently failing to render in the page. */
        $widgetType = ConfigManager::getConfigurationValue('COMFINO_WIDGET_TYPE');

        if (!is_string($widgetType) || $widgetType === '' || $widgetType === 'error') {
            return '';
        }

        $widgetOfferTypes = ConfigManager::getConfigurationValue('COMFINO_WIDGET_OFFER_TYPES');

        if (!is_array($widgetOfferTypes) || $widgetOfferTypes === []) {
            return '';
        }

        $productId = ($rawValue = $this->request->getParam('product_id')) !== null ? (int) $rawValue : null;

        try {
            /* Pass raw values through to the helper — it serializes arrays to JS literals itself. Pre-serializing
               OFFER_TYPES (string[]) here would re-JSON-encode it, producing a quoted string the widget frontend
               doesn't recognize. */
            return WidgetInitScriptHelper::renderWidgetInitScript(
                ConfigManager::getCurrentWidgetCode($productId),
                array_combine(
                    WidgetInitScriptHelper::WIDGET_INIT_PARAMS,
                    ConfigManager::getConfigurationValues(
                        'widget_settings',
                        [
                            'COMFINO_WIDGET_KEY',
                            'COMFINO_WIDGET_PRICE_SELECTOR',
                            'COMFINO_WIDGET_TARGET_SELECTOR',
                            'COMFINO_WIDGET_PRICE_OBSERVER_SELECTOR',
                            'COMFINO_WIDGET_PRICE_OBSERVER_LEVEL',
                            'COMFINO_WIDGET_TYPE',
                            'COMFINO_WIDGET_OFFER_TYPES',
                            'COMFINO_WIDGET_EMBED_METHOD',
                            'COMFINO_WIDGET_SHOW_PROVIDER_LOGOS',
                            'COMFINO_WIDGET_CUSTOM_BANNER_CSS_URL',
                            'COMFINO_WIDGET_CUSTOM_CALCULATOR_CSS_URL',
                        ]
                    )
                ),
                ConfigManager::getWidgetVariables($productId)
            );
        } catch (\Throwable $e) {
            ErrorLogger::sendError(
                $e,
                'Widget script endpoint',
                (string) $e->getCode(),
                $e->getMessage(),
                null,
                null,
                null,
                $e->getTraceAsString()
            );

            return '';
        }
    }
}
