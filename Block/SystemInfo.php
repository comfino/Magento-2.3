<?php

namespace Comfino\ComfinoGateway\Block;

use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Model\Connector\Service\ApplicationService;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SystemInfo extends Field
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
        $infoMessages = [];
        $successMessages = [];
        $warningMessages = [];
        $errorMessages = [];

        $systemInfo = sprintf(
            'Magento Comfino %s, Magento %s, PHP %s, web server %s, database %s',
            $this->helper->getModuleVersion(),
            $this->helper->getShopVersion(),
            PHP_VERSION,
            $_SERVER['SERVER_SOFTWARE'],
            $this->helper->getDatabaseVersion()
        );

        $bodyHtml = '<p><label class="label">' . __('System information') . '</label></p>';

        $infoMessages[] = $systemInfo;

        if ($this->helper->isSandboxEnabled()) {
            $warningMessages[] = __('Developer mode is active. You are using test environment.');

            if (!empty($this->helper->getApiKey())) {
                if ($this->applicationService->isShopAccountActive()) {
                    $successMessages[] = __('Test account is active.');
                } else if (count($this->applicationService->getLastErrors())) {
                    $errorMessages = array_merge($errorMessages, $this->applicationService->getLastErrors());

                    if ($this->applicationService->getLastResponseCode() === 401) {
                        $errorMessages[] = __('Invalid test API key.');
                    }
                } else {
                    $warningMessages[] = __('Test account is not active.');
                }
            } else {
                $errorMessages[] = __('Test API key not present.');
            }
        } elseif (!empty($this->helper->getApiKey())) {
            $successMessages[] = __('Production mode is active.');

            if ($this->applicationService->isShopAccountActive()) {
                $successMessages[] = __('Production account is active.');
            } else if (count($this->applicationService->getLastErrors())) {
                $errorMessages = array_merge($errorMessages, $this->applicationService->getLastErrors());

                if ($this->applicationService->getLastResponseCode() === 401) {
                    $errorMessages[] = __('Invalid production API key.');
                }
            } else {
                $warningMessages[] = __('Production account is not active.');
            }
        } else {
            $errorMessages[] = __('Production API key not present.');
        }

        foreach ($infoMessages as $infoMessage) {
            $bodyHtml .= '<div class="message message-info">' . $infoMessage . '</div>';
        }

        foreach ($errorMessages as $errorMessage) {
            $bodyHtml .= '<div class="message message-error">' . $errorMessage . '</div>';
        }

        foreach ($warningMessages as $warningMessage) {
            $bodyHtml .= '<div class="message message-warning">' . $warningMessage . '</div>';
        }

        foreach ($successMessages as $successMessage) {
            $bodyHtml .= '<div class="message message-success">' . $successMessage . '</div>';
        }

        return $bodyHtml;
    }
}
