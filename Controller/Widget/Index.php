<?php

namespace Comfino\ComfinoGateway\Controller\Widget;

use Comfino\ComfinoGateway\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;

class Index extends Action
{
    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var Data
     */
    private $helper;

    public function __construct(Context $context, RawFactory $resultRawFactory, Data $helper)
    {
        $this->resultRawFactory = $resultRawFactory;
        $this->helper = $helper;

        parent::__construct($context);
    }

    /**
     * Returns widget initialization code.
     *
     * @return Raw
     */
    public function execute(): Raw
    {
        $result = $this->resultRawFactory->create();
        $result->setHeader('Content-Type', 'application/javascript');

        if ($this->helper->isWidgetActive()) {
            $result->setContents(
                str_replace(
                    [
                        '{WIDGET_KEY}',
                        '{WIDGET_PRICE_SELECTOR}',
                        '{WIDGET_TARGET_SELECTOR}',
                        '{WIDGET_PRICE_OBSERVER_SELECTOR}',
                        '{WIDGET_PRICE_OBSERVER_LEVEL}',
                        '{WIDGET_TYPE}',
                        '{OFFER_TYPE}',
                        '{EMBED_METHOD}',
                        '{WIDGET_SCRIPT_URL}',
                    ],
                    [
                        $this->helper->getWidgetKey(),
                        $this->helper->getWidgetPriceSelector(),
                        $this->helper->getWidgetTargetSelector(),
                        $this->helper->getPriceObserverSelector(),
                        $this->helper->getPriceObserverLevel(),
                        $this->helper->getWidgetType(),
                        $this->helper->getWidgetOfferType(),
                        $this->helper->getWidgetEmbedMethod(),
                        $this->helper->getWidgetScriptUrl(),
                    ],
                    $this->helper->getWidgetCode()
                )
            );
        }

        return $result;
    }
}
