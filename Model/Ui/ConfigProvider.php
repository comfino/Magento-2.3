<?php

namespace Comfino\ComfinoGateway\Model\Ui;

use Comfino\ComfinoGateway\Helper\Data;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'comfino';

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Session
     */
    protected $session;

    public function __construct(Data $helper, Session $session)
    {
        $this->helper = $helper;
        $this->session = $session;
    }

    public function getConfig(): array
    {
        return [
            'Comfino' => [
                'frontendScriptURL' => $this->helper->getFrontendScriptUrl(),
                'frontendRendererOptions' => [
                    'platform' => 'magento',
                    'platformVersion' => $this->helper->getShopVersion(),
                    'platformDomain' => $this->helper->getShopDomain(),
                    'pluginVersion' => $this->helper->getModuleVersion(),
                    'offersURL' => $this->helper->getOffersUrl($this->session->getQuote()->getGrandTotal()),
                    'language' => $this->helper->getShopLanguage(),
                    'currency' => $this->session->getQuote()->getQuoteCurrencyCode(),
                    'cartTotal' => $this->session->getQuote()->getGrandTotal(),
                    'cartTotalFormatted' => $this->helper->formatPrice($this->session->getQuote()->getGrandTotal()),
                ]
            ]
        ];
    }
}
