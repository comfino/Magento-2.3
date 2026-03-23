<?php

namespace Comfino\ComfinoGateway\Controller\Widget;

use Comfino\Common\Frontend\WidgetInitScriptHelper;
use Comfino\Configuration\ConfigManager;
use Comfino\ErrorLogger;
use Comfino\Extended\Api\Serializer\Json as JsonSerializer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;

class Index extends Action
{
    private RawFactory $resultRawFactory;

    public function __construct(Context $context, RawFactory $resultRawFactory)
    {
        $this->resultRawFactory = $resultRawFactory;

        parent::__construct($context);
    }

    /**
     * Returns widget initialization code.
     */
    public function execute(): Raw
    {
        ErrorLogger::init();

        $result = $this->resultRawFactory->create();
        $result->setHeader('Content-Type', 'application/javascript');

        if (ConfigManager::isWidgetEnabled() && ConfigManager::getWidgetKey() !== '') {
            if (($productId = $this->getRequest()->getParam('product_id')) !== null) {
                $productId = (int) $productId;
            }

            $serializer = new JsonSerializer();

            try {
                $response = WidgetInitScriptHelper::renderWidgetInitScript(
                    ConfigManager::getCurrentWidgetCode($productId),
                    array_combine(
                        WidgetInitScriptHelper::WIDGET_INIT_PARAMS,
                        array_map(
                            static function ($optionValue) use ($serializer) {
                                return is_array($optionValue) ? $serializer->serialize($optionValue) : $optionValue;
                            },
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
                $response = '';
            }
        } else {
            $response = '';
        }

        $result->setContents($response);

        return $result;
    }
}
