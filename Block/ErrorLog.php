<?php

namespace Comfino\ComfinoGateway\Block;

use Comfino\ComfinoGateway\Helper\ErrorLog as ErrorLogHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ErrorLog extends Field
{
    private const ERROR_LOG_NUM_LINES = 100;

    /**
     * @var ErrorLogHelper
     */
    private $helper;

    public function __construct(ErrorLogHelper $helper, Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $this->helper = $helper;
    }

    public function render(AbstractElement $element): string
    {
        $bodyHtml = '<p><label class="label">' . __('Errors log') . '</label></p>';
        $bodyHtml .= '<textarea cols="60" rows="20" readonly="readonly">' . $this->helper->getErrorLog(self::ERROR_LOG_NUM_LINES) . '</textarea>';

        return $bodyHtml;
    }
}
