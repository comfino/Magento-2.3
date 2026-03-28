<?php

namespace Comfino\ComfinoGateway\Block;

use Comfino\Api\ApiClient;
use Comfino\Api\Exception\AccessDenied;
use Comfino\Api\Exception\AuthorizationError;
use Comfino\ComfinoGateway\Helper\Data;
use Comfino\Configuration\ConfigManager;
use Comfino\PluginShared\CacheManager;
use Comfino\Update\UpdateManager;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SystemInfo extends Field
{
    private Data $helper;
    private DirectoryList $dirList;

    public function __construct(Data $helper, DirectoryList $dirList, Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $this->helper = $helper;
        $this->dirList = $dirList;
    }

    public function render(AbstractElement $element): string
    {
        CacheManager::init($this->dirList->getPath('var'));

        $infoMessages = [];
        $successMessages = [];
        $warningMessages = [];
        $errorMessages = [];

        $moduleVersion = $this->helper->getModuleVersion();

        $systemInfo = sprintf(
            'Magento Comfino %s, Magento %s, PHP %s, web server %s, database %s',
            $moduleVersion,
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

        $updateInfo = UpdateManager::checkForUpdates($moduleVersion);

        if (!empty($updateInfo['github_version'])) {
            $githubVersion = $updateInfo['github_version'];
            $isNewer = version_compare($githubVersion, $moduleVersion, '>');
            $versionColor = $isNewer ? 'orange' : 'green';
            $versionNote = $isNewer
                ? sprintf('(<a href="%s" target="_blank">%s</a>)', htmlspecialchars($updateInfo['release_notes_url'] ?? ''), __('Download from GitHub'))
                : '(' . __('up to date') . ')';
            $versionInfo = sprintf(
                '<b>%s:</b> <b style="color: %s">%s</b> %s',
                __('Latest available version'),
                $versionColor,
                htmlspecialchars($githubVersion),
                $versionNote
            );
            if (!empty($updateInfo['checked_at'])) {
                $versionInfo .= sprintf(
                    ' <small style="color: #666">%s: %s UTC</small>',
                    __('Last checked'),
                    \DateTime::createFromFormat('U', (string) $updateInfo['checked_at'])->format('Y-m-d H:i:s')
                );
            }
            $infoMessages[] = $versionInfo;
        } elseif (!empty($updateInfo['error'])) {
            $infoMessages[] = sprintf('<b>%s:</b> <span style="color: #888">%s</span>', __('Latest available version'), htmlspecialchars($updateInfo['error']));
        } else {
            $infoMessages[] = sprintf('<b>%s:</b> <span style="color: #888">%s</span>', __('Latest available version'), __('Checking...'));
        }

        $cacheRootPath = $this->dirList->getPath('var');
        $cacheDirPath = CacheManager::getCacheFullPath();
        $devEnvActive = getenv('COMFINO_DEV_ENV') === 'TRUE';

        $infoMessages[] = sprintf(
            '<b>%s:</b> %s%s',
            __('Cache root directory writable'),
            is_writable($cacheRootPath) ? '<b style="color: green">YES</b>' : '<b style="color: red">NO</b>',
            $devEnvActive ? ' (<i>' . htmlspecialchars($cacheRootPath) . '</i>)' : ''
        );
        $infoMessages[] = sprintf(
            '<b>%s:</b> %s%s',
            __('Cache directory writable'),
            (is_dir($cacheDirPath) && is_writable($cacheDirPath)) || (!is_dir($cacheDirPath) && is_writable($cacheRootPath))
                ? '<b style="color: green">YES</b>'
                : '<b style="color: red">NO</b>',
            $devEnvActive ? ' (<i>' . htmlspecialchars($cacheDirPath) . '</i>)' : ''
        );

        $bodyHtml = '<p><label class="label">' . __('System information') . '</label></p>';

        if (ConfigManager::isSandboxMode()) {
            $warningMessages[] = __('Developer mode is active. You are using test environment.');

            if (!empty(ConfigManager::getApiKey())) {
                try {
                    ApiClient::getInstance()->isShopAccountActive();
                    $successMessages[] = __('Test account is active.');
                } catch (AuthorizationError|AccessDenied $e) {
                    $errorMessages[] = $e->getMessage();
                    $errorMessages[] = __('Invalid test API key.');
                } catch (\Throwable $e) {
                    $warningMessages[] = __('Test account is not active.');
                }
            } else {
                $errorMessages[] = __('Test API key not present.');
            }
        } elseif (!empty(ConfigManager::getApiKey())) {
            $successMessages[] = __('Production mode is active.');

            try {
                ApiClient::getInstance()->isShopAccountActive();
                $successMessages[] = __('Production account is active.');
            } catch (AuthorizationError|AccessDenied $e) {
                $errorMessages[] = $e->getMessage();
                $errorMessages[] = __('Invalid production API key.');
            } catch (\Throwable $e) {
                $warningMessages[] = __('Production account is not active.');
            }
        } else {
            $errorMessages[] = __('Production API key not present.');
        }

        if ($devEnvActive) {
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
