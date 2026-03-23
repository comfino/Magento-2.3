<?php

namespace Comfino\ComfinoGateway\Model\Ui;

use Comfino\ComfinoGateway\Helper\Data;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'comfino';

    protected Data $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Returns minimal checkout configuration for Comfino payment method.
     * Paywall URL and iframe init options are now rendered server-side in comfino.phtml.
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => true,
                    'pluginVersion' => $this->helper->getModuleVersion(),
                ]
            ]
        ];
    }
}
