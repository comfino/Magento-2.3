<?php

namespace Comfino\ComfinoGateway\Observer;

use Comfino\ComfinoGateway\Helper\Data;
use Comfino\ComfinoGateway\Model\Connector\Service\ApplicationService;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigObserver implements ObserverInterface
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ApplicationService
     */
    private $applicationService;

    public function __construct(WriterInterface $configWriter, Data $helper, ApplicationService $applicationService)
    {
        $this->configWriter = $configWriter;
        $this->helper = $helper;
        $this->applicationService = $applicationService;
    }

    public function execute(Observer $observer)
    {
        if (!empty($this->helper->getApiKey())) {
            $this->configWriter->save(
                Data::XML_PATH_WIDGET_KEY,
                $this->applicationService->getWidgetKey(),
                ScopeInterface::SCOPE_STORE
            );
        }
    }
}
