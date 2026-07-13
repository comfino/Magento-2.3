<?php

namespace Comfino\ComfinoGateway\Block;

use Comfino\ComfinoGateway\Helper\Data;
use Comfino\Configuration\ConfigManager;
use Comfino\Update\UpdateManager;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ComfinoLogo extends Field
{
    /**
     * Allowlist for the release "what's new" HTML, mirroring the server-side sanitizer. Magento's escapeHtml keeps
     * these tags (and their safe attributes) and escapes everything else.
     */
    private const RELEASE_DESCRIPTION_ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'b', 'i', 'ul', 'ol', 'li', 'h3', 'h4', 'code', 'pre', 'a', 'span', 'div', 'img'
    ];

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

        return $blockHtml . $this->renderReleaseDescription();
    }

    /**
     * "What's new" HTML of the latest available release, shown under the logo/version - but only when a newer version
     * is available (hidden when up to date). Server-sanitized already; re-escaped here with Magento's escapeHtml
     * allowlist, so the output stays safe per marketplace requirements.
     */
    private function renderReleaseDescription(): string
    {
        $updateInfo = UpdateManager::checkForUpdates($this->helper->getModuleVersion());

        if (empty($updateInfo['update_available']) || empty($updateInfo['description_html'])) {
            return '';
        }

        $currentVersion = $this->helper->getModuleVersion();
        $newVersion = $updateInfo['github_version'] ?? '';

        $blockHtml = '<div class="comfino-update-available-message" style="margin-top: 10px; font-weight: bold">' .
            $this->escapeHtml((string) __(
                'New Comfino %1 module version is available. You are using %2 version. Please update your Comfino module.',
                $newVersion,
                $currentVersion
            )) . '</div>';

        $blockHtml .= '<div class="comfino-release-description" style="margin-top: 10px">' .
            $this->escapeHtml($updateInfo['description_html'], self::RELEASE_DESCRIPTION_ALLOWED_TAGS) . '</div>';

        return $blockHtml;
    }
}
