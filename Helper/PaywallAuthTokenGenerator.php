<?php

namespace Comfino\ComfinoGateway\Helper;

use Comfino\Configuration\ConfigManager;
use Comfino\Extended\Auth\PaywallAuthTokenGenerator as PaywallAuthTokenGeneratorBase;
use Magento\Framework\App\Helper\AbstractHelper;

class PaywallAuthTokenGenerator extends AbstractHelper
{
    public function generateAuthToken(): string
    {
        return PaywallAuthTokenGeneratorBase::generateAuthToken(
            ConfigManager::getWidgetKey() ?? '',
            ConfigManager::getApiKey() ?? ''
        );
    }

    public function generateLoggingToken(): string
    {
        ConfigManager::refreshErrorLoggingTokenIfNeeded();

        return PaywallAuthTokenGeneratorBase::generateLoggingToken(
            ConfigManager::getWidgetKey() ?? '',
            ConfigManager::getErrorLoggingAccessToken()
        );
    }
}