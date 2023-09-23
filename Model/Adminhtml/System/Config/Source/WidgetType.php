<?php

namespace Comfino\ComfinoGateway\Model\Adminhtml\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class WidgetType implements OptionSourceInterface
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
