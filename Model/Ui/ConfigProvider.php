<?php

namespace Comfino\ComfinoGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'comfino';

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return [];
    }
}
