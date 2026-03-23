<?php

namespace Comfino\ComfinoGateway\Block;

use Comfino\Api\ApiClient;
use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Model\Connector\Service\ApplicationService;
use Comfino\Configuration\ConfigManager;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SystemInfo extends Field
{
    private ApplicationService $applicationService;
    private Data $helper;

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
            $_SERVER['SERVER_SOFTWARE'] ?? 'n/a',
            $this->helper->getDatabaseVersion()
        );
        $systemInfo .= '<hr><b>' . __('Comfino API host') . ':</b> ' . ApiClient::getInstance()->getApiHost();

        $infoMessages[] = $systemInfo;
        $infoMessages[] = sprintf(
            '<b>%s:</b> %s UTC',
            __('Plugin build time'),
            \DateTime::createFromFormat('U', (string) Data::BUILD_TS)->format('Y-m-d H:i:s')
        );
        $infoMessages[] = '<b>' . __('Shop domain') . ':</b> ' . htmlspecialchars($this->helper->getShopDomain());
        $infoMessages[] = '<b>' . __('Widget key') . ':</b> ' . htmlspecialchars((string) ConfigManager::getWidgetKey());

        $bodyHtml = '<p><label class="label">' . __('System information') . '</label></p>';

        if (ConfigManager::isSandboxMode()) {
            $warningMessages[] = __('Developer mode is active. You are using test environment.');

            if (!empty(ConfigManager::getApiKey())) {
                if ($this->applicationService->isShopAccountActive()) {
                    $successMessages[] = __('Test account is active.');
                } elseif (count($this->applicationService->getLastErrors())) {
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
        } elseif (!empty(ConfigManager::getApiKey())) {
            $successMessages[] = __('Production mode is active.');

            if ($this->applicationService->isShopAccountActive()) {
                $successMessages[] = __('Production account is active.');
            } elseif (count($this->applicationService->getLastErrors())) {
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

        if (getenv('COMFINO_DEV_ENV') === 'TRUE') {
            $devEnvVarNames = [
                'COMFINO_DEV_ENV',
                'COMFINO_DEV_API_HOST',
                'COMFINO_DEV_SDK_SCRIPT_URL',
                'COMFINO_DEV_WIDGET_SCRIPT_URL',
            ];

            $devEnvVars = [];
            foreach ($devEnvVarNames as $varName) {
                $value = getenv($varName);
                $devEnvVars[] = '<li><b>' . $varName . '</b> = "' . htmlspecialchars($value !== false ? $value : '') . '"</li>';
            }

            $hiddenOptions = ConfigManager::getConfigurationValues('hidden_settings');
            $hiddenItems = [];
            foreach ($hiddenOptions as $optionName => $optionValue) {
                if (is_array($optionValue) || is_bool($optionValue)) {
                    $optionValue = json_encode($optionValue);
                }
                $optionValue = (string) $optionValue;
                if (strlen($optionValue) > 200) {
                    $optionValue = substr($optionValue, 0, 200) . '...';
                }
                $hiddenItems[] = '<li><b>' . $optionName . '</b> = "' . htmlspecialchars($optionValue) . '"</li>';
            }

            $warningMessages[] = sprintf(
                '<b>%s:</b> %s<br><b>%s:</b><ul>%s</ul><b>%s:</b><ul>%s</ul>',
                __('Plugin dev-debug mode'),
                ConfigManager::useDevEnvVars() ? __('active') : __('inactive'),
                __('Environment variables'),
                implode('', $devEnvVars),
                __('Internal configuration'),
                implode('', $hiddenItems)
            );
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
