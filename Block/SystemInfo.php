<?php

namespace Comfino\ComfinoGateway\Block;

use Comfino\ComfinoGateway\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SystemInfo extends Field
{
    /**
     * @var Data
     */
    private $helper;

    public function __construct(Data $helper, Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $this->helper = $helper;
    }

    public function render(AbstractElement $element): string
    {
        $systemInfo = sprintf(
            'Magento Comfino %s, Magento %s, PHP %s, web server %s, database %s',
            $this->helper->getModuleVersion(),
            $this->helper->getShopVersion(),
            PHP_VERSION,
            $_SERVER['SERVER_SOFTWARE'],
            $this->helper->getDatabaseVersion()
        );

        $bodyHtml = '<p><label class="label">' . __('System information') . '</label></p>';
        $bodyHtml .= '<div class="message message-info">' . $systemInfo . '</div>';

        return $bodyHtml;
    }
}
