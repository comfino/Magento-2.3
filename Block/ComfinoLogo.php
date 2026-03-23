<?php

namespace Comfino\ComfinoGateway\Block;

use Comfino\ComfinoGateway\Helper\Data;
use Comfino\Configuration\ConfigManager;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ComfinoLogo extends Field
{
    private Data $helper;

    public function __construct(Data $helper, Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $this->helper = $helper;
    }

    public function render(AbstractElement $element): string
    {
        $logoUrl = ConfigManager::getLogoUrl();

        $blockHtml = $logoUrl !== ''
            ? '<img style="width: 300px" src="' . htmlspecialchars($logoUrl, ENT_QUOTES) . '" alt="Comfino logo"> '
            : '';

        $blockHtml .= '<span style="font-weight: bold; font-size: 16px; vertical-align: bottom">'
            . htmlspecialchars($this->helper->getModuleVersion(), ENT_QUOTES)
            . '</span>';

        return $blockHtml;
    }
}
