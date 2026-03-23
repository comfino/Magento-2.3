<?php

namespace Comfino\ComfinoGateway\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DevEnvField extends Field
{
    public function render(AbstractElement $element): string
    {
        if (getenv('COMFINO_DEV_ENV') !== 'TRUE') {
            return '';
        }

        return parent::render($element);
    }
}
