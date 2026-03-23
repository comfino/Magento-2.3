<?php

namespace Comfino\ComfinoGateway\Block;

use Comfino\ErrorLogger;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;

class ErrorLog extends Field
{
    private const ERROR_LOG_NUM_LINES = 100;

    private UrlInterface $urlBuilder;

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $this->urlBuilder = $context->getUrlBuilder();
    }

    public function render(AbstractElement $element): string
    {
        $clearUrl = $this->urlBuilder->getUrl('comfino/log/clear', ['type' => 'error']);
        $textareaId = 'comfino_error_log_content';

        $bodyHtml = '<p><label class="label">' . __('Errors log') . '</label></p>';
        $bodyHtml .= '<textarea id="' . $textareaId . '" cols="60" rows="20" readonly="readonly">'
            . ErrorLogger::getErrorLog(self::ERROR_LOG_NUM_LINES)
            . '</textarea>';
        $bodyHtml .= '<p>'
            . '<button type="button" class="action-default scalable" onclick="comfinoClearLog(\'' . $clearUrl . '\', \'' . $textareaId . '\')">'
            . __('Clear errors log')
            . '</button>'
            . '</p>';
        $bodyHtml .= '<script>'
            . 'if (typeof comfinoClearLog === "undefined") {'
            . '  window.comfinoClearLog = function(url, textareaId) {'
            . '    if (!confirm(' . json_encode((string) __('Are you sure you want to clear the log?')) . ')) { return; }'
            . '    fetch(url, { method: "POST", headers: { "X-Requested-With": "XMLHttpRequest" } })'
            . '      .then(function(r) { return r.json(); })'
            . '      .then(function(data) {'
            . '        if (data.success) { document.getElementById(textareaId).value = ""; }'
            . '        else { alert(data.message || "Error clearing log."); }'
            . '      })'
            . '      .catch(function() { alert("Request failed."); });'
            . '  };'
            . '}'
            . '</script>';

        return $bodyHtml;
    }
}
