<?php

namespace Comfino\ComfinoGateway\Block;

use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Model\Connector\Service\ApplicationService;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ComfinoLogo extends Field
{
    /**
     * @var ApplicationService
     */
    private $applicationService;

    /**
     * @var Data
     */
    private $helper;

    public function __construct(ApplicationService $applicationService, Data $helper, Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $this->applicationService = $applicationService;
        $this->helper = $helper;
    }

    public function render(AbstractElement $element): string
    {
        $logoUrl = $this->applicationService->getLogoUrl();

        if ($logoUrl !== '') {
            $blockHtml = '<img style="width: 300px" src="' . $logoUrl . '" alt="Comfino logo"> ';
        } else {
            $blockHtml = '';
        }

        $blockHtml .= '<span style="font-weight: bold; font-size: 16px; vertical-align: bottom">' . $this->helper->getModuleVersion() . '</span>';

        return $blockHtml;
    }
}
