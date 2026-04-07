<?php

namespace Comfino\ComfinoGateway\Model\Adminhtml\System\Config\Source;

use Comfino\Configuration\SettingsManager;
use Magento\Framework\Data\OptionSourceInterface;

class WidgetType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return SettingsManager::getWidgetTypesSelectList();
    }
}
