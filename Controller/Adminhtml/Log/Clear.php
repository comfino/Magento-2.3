<?php

namespace Comfino\ComfinoGateway\Controller\Adminhtml\Log;

use Comfino\DebugLogger;
use Comfino\ErrorLogger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Clear extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Config::config';

    private JsonFactory $resultJsonFactory;

    public function __construct(Context $context, JsonFactory $resultJsonFactory)
    {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $type = $this->getRequest()->getParam('type');

        try {
            if ($type === 'error') {
                ErrorLogger::clearLogs();
            } elseif ($type === 'debug') {
                DebugLogger::clearLogs();
            } else {
                return $result->setData(['success' => false, 'message' => 'Unknown log type.']);
            }

            return $result->setData(['success' => true]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
