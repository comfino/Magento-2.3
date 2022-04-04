<?php

namespace Comfino\ComfinoGateway\Model\Adminhtml\System\Config\Source;

class WidgetEmbedMethod implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'INSERT_INTO_FIRST', 'label' => 'INSERT_INTO_FIRST'],
            ['value' => 'INSERT_INTO_LAST', 'label' => 'INSERT_INTO_LAST'],
            ['value' => 'INSERT_BEFORE', 'label' => 'INSERT_BEFORE'],
            ['value' => 'INSERT_AFTER', 'label' => 'INSERT_AFTER'],
        ];
    }
}
