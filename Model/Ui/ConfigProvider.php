<?php

namespace Comfino\ComfinoGateway\Model\Ui;

use Comfino\ComfinoGateway\Helper\Data;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'comfino';

    /**
     * @var Data
     */
    protected $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
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
                    'offersURL' => '',
                    'language' => $this->helper->getShopLanguage(),
                    'currency' => 'PLN',
                    'cartTotal' => 0.0,
                    'cartTotalFormatted' => '',
                ]
            ]
        ];
    }
}
