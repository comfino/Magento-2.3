<?php

namespace Comfino\ComfinoGateway\Model\Adminhtml\System\Config\Source;

class WidgetType implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'simple', 'label' => __('Textual widget')],
            ['value' => 'mixed', 'label' => __('Graphical widget with banner')],
            ['value' => 'with-modal', 'label' => __('Graphical widget with installments calculator')],
        ];
    }
}
