<?php

namespace Comfino\ComfinoGateway\Model\Adminhtml\System\Config\Source;

class WidgetOfferType implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'INSTALLMENTS_ZERO_PERCENT', 'label' => __('Zero percent installments')],
            ['value' => 'CONVENIENT_INSTALLMENTS', 'label' => __('Convenient installments')],
        ];
    }
}
