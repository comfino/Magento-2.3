<?php

namespace Comfino\ComfinoGateway\Model\Adminhtml\System\Config\Source;

use Comfino\Configuration\SettingsManager;
use Comfino\FinancialProduct\ProductTypesListTypeEnum;
use Magento\Framework\Data\OptionSourceInterface;

class WidgetOfferType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return SettingsManager::getProductTypesSelectList(ProductTypesListTypeEnum::LIST_TYPE_WIDGET);
    }
}
