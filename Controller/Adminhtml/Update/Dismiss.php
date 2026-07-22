<?php

namespace Comfino\ComfinoGateway\Controller\Adminhtml\Update;

use Comfino\ComfinoGateway\Model\System\Message\UpdateAvailable;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\FlagManager;

/**
 * Dismisses the "update available" admin system message for a specific version. The version is stored via
 * FlagManager, so the notice stays hidden until a newer release appears. Reached from the Dismiss link in the
 * notice text; the backend URL carries the admin secret key, so the GET request is CSRF-protected.
 */
class Dismiss extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Config::config';

    private FlagManager $flagManager;

    public function __construct(Context $context, FlagManager $flagManager)
    {
        parent::__construct($context);

        $this->flagManager = $flagManager;
    }

    public function execute()
    {
        $version = trim((string) $this->getRequest()->getParam('version', ''));

        if ($version !== '') {
            $this->flagManager->saveFlag(UpdateAvailable::DISMISS_FLAG, $version);
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setRefererOrBaseUrl();
    }
}