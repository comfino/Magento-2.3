<?php

namespace Comperia\ComperiaGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'comperiapayment';

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return [];
    }
}
